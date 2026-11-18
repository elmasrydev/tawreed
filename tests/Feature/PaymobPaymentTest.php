<?php

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\PaymobGateway;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * A Paymob "transaction processed" object for the payment, as Paymob posts it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function paymobTransaction(Payment $payment, array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 192036465,
        'pending' => false,
        'amount_cents' => $payment->totalCents(),
        'success' => true,
        'is_auth' => false,
        'is_capture' => false,
        'is_standalone_payment' => true,
        'is_voided' => false,
        'is_refunded' => false,
        'is_3d_secure' => true,
        'integration_id' => 5975983,
        'has_parent_transaction' => false,
        'error_occured' => false,
        'currency' => 'EGP',
        'created_at' => '2026-11-18T15:04:12.000000',
        'owner' => 1832,
        'source_data' => ['pan' => '2346', 'type' => 'card', 'sub_type' => 'MasterCard'],
        'order' => ['id' => 217503754, 'merchant_order_id' => $payment->reference],
        'data' => ['message' => 'Approved'],
    ], $overrides);
}

beforeEach(function (): void {
    seedReferenceData();

    $this->gateway = new PaymobGateway(
        baseUrl: 'https://accept.paymob.com',
        secretKey: 'sk_test_secret',
        publicKey: 'egy_pk_test_public',
        hmacSecret: 'hmac_test_secret',
        integrationIds: [5975983],
    );
    $this->app->instance(PaymentGateway::class, $this->gateway);

    $this->supplier = User::factory()->supplier()->create();
    $this->monthly = Plan::where('slug', 'monthly')->firstOrFail();
    $this->payment = Payment::factory()->forPlan($this->monthly)->create(['supplier_id' => $this->supplier->id]);

    $this->postWebhook = function (array $transaction, ?string $hmac = null) {
        return $this->postJson(
            route('webhooks.paymob', ['hmac' => $hmac ?? $this->gateway->sign(Arr::dot($transaction))]),
            ['type' => 'TRANSACTION', 'obj' => $transaction],
        );
    };
});

it('opens a paymob checkout for the vat-inclusive total and redirects the supplier to it', function (): void {
    Http::fake(['accept.paymob.com/v1/intention/*' => Http::response([
        'id' => 'pi_test_123',
        'client_secret' => 'egy_csk_test_abc',
    ])]);

    $this->actingAs($this->supplier)
        ->post(route('supplier.subscription.subscribe', $this->monthly))
        ->assertRedirect('https://accept.paymob.com/unifiedcheckout/?publicKey=egy_pk_test_public&clientSecret=egy_csk_test_abc');

    $payment = Payment::latest('id')->first();

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->gateway)->toBe('paymob')
        ->and($payment->provider_intention_id)->toBe('pi_test_123')
        ->and(Subscription::count())->toBe(0);

    Http::assertSent(fn (HttpRequest $request): bool => $request->hasHeader('Authorization', 'Token sk_test_secret')
        && $request['amount'] === 28500
        && $request['currency'] === 'EGP'
        && $request['payment_methods'] === [5975983]
        && $request['items'][0]['amount'] === 28500
        && $request['special_reference'] === $payment->reference
        && $request['notification_url'] === route('webhooks.paymob')
        && $request['billing_data']['email'] === $this->supplier->email);
});

it('keeps the supplier on checkout when paymob cannot open a session', function (): void {
    Http::fake(['accept.paymob.com/*' => Http::response(['detail' => 'error'], 500)]);

    $this->actingAs($this->supplier)
        ->from(route('supplier.subscription.checkout', $this->monthly))
        ->post(route('supplier.subscription.subscribe', $this->monthly))
        ->assertRedirect(route('supplier.subscription.checkout', $this->monthly))
        ->assertSessionHas('status', __('subscription.payment_failed'));

    expect(Payment::latest('id')->first()->status)->toBe(PaymentStatus::Failed);
});

it('signs callbacks with paymob\'s field order and formatting', function (): void {
    $transaction = paymobTransaction($this->payment);

    $concatenated = $transaction['amount_cents'].'2026-11-18T15:04:12.000000EGPfalsefalse192036465'
        .'5975983truefalsefalsefalsetruefalse2175037541832false2346MasterCardcardtrue';

    expect($this->gateway->sign(Arr::dot($transaction)))
        ->toBe(hash_hmac('sha512', $concatenated, 'hmac_test_secret'));
});

it('activates the plan and issues an invoice from a verified webhook', function (): void {
    ($this->postWebhook)(paymobTransaction($this->payment))->assertOk();

    $payment = $this->payment->fresh();
    $subscription = Subscription::sole();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->subscription_id)->toBe($subscription->id)
        ->and($payment->provider_transaction_id)->toBe('192036465')
        ->and($payment->method)->toBe('MasterCard')
        ->and($subscription->payment_ref)->toBe('192036465')
        ->and($this->supplier->fresh()->hasActiveSubscription())->toBeTrue()
        ->and((float) Invoice::sole()->total_egp)->toBe(285.0);
});

it('ignores a repeated webhook for an already paid payment', function (): void {
    $transaction = paymobTransaction($this->payment);

    ($this->postWebhook)($transaction)->assertOk();
    ($this->postWebhook)($transaction)->assertOk();

    expect(Subscription::count())->toBe(1)
        ->and(Invoice::count())->toBe(1);
});

it('rejects a webhook with a forged signature', function (): void {
    ($this->postWebhook)(paymobTransaction($this->payment), str_repeat('a', 128))->assertForbidden();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(Subscription::count())->toBe(0);
});

it('rejects a webhook whose fields were altered after signing', function (): void {
    $transaction = paymobTransaction($this->payment);
    $hmac = $this->gateway->sign(Arr::dot($transaction));

    ($this->postWebhook)([...$transaction, 'success' => true, 'amount_cents' => 100], $hmac)->assertForbidden();

    expect(Subscription::count())->toBe(0);
});

it('does not activate when the paid amount differs from the order', function (): void {
    ($this->postWebhook)(paymobTransaction($this->payment, ['amount_cents' => 100]))->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($this->payment->fresh()->failure_reason)->toBe('amount_mismatch')
        ->and(Subscription::count())->toBe(0);
});

it('records a declined card and still accepts a later successful retry', function (): void {
    ($this->postWebhook)(paymobTransaction($this->payment, [
        'id' => 1001,
        'success' => false,
        'data' => ['message' => 'Insufficient funds'],
    ]))->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($this->payment->fresh()->failure_reason)->toBe('Insufficient funds')
        ->and(Subscription::count())->toBe(0);

    ($this->postWebhook)(paymobTransaction($this->payment, ['id' => 1002]))->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and(Subscription::count())->toBe(1);
});

it('leaves a pending transaction pending', function (): void {
    ($this->postWebhook)(paymobTransaction($this->payment, ['pending' => true, 'success' => false]))->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('acknowledges non-transaction callbacks without acting on them', function (): void {
    $this->postJson(route('webhooks.paymob'), ['type' => 'TOKEN', 'obj' => ['token' => 'x']])->assertOk();

    expect(Subscription::count())->toBe(0);
});

it('settles the payment from paymob\'s signed browser redirect', function (): void {
    $transaction = paymobTransaction($this->payment);
    $flat = Arr::dot($transaction);

    $query = [
        ...collect($flat)->except(['order.id', 'order.merchant_order_id'])
            ->map(fn (mixed $value): string => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value)
            ->all(),
        'order' => (string) $transaction['order']['id'],
        'merchant_order_id' => $this->payment->reference,
        'hmac' => $this->gateway->sign($flat),
    ];

    $this->actingAs($this->supplier)
        ->get(route('supplier.subscription.payment-return').'?'.http_build_query($query))
        ->assertRedirect(route('supplier.subscription'))
        ->assertSessionHas('status', __('subscription.activated'));

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('turns away a redirect with an invalid signature', function (): void {
    $this->actingAs($this->supplier)
        ->get(route('supplier.subscription.payment-return', [
            'merchant_order_id' => $this->payment->reference,
            'success' => 'true',
            'hmac' => 'forged',
        ]))
        ->assertRedirect(route('supplier.subscription'))
        ->assertSessionHas('status', __('subscription.payment_failed'));

    expect(Subscription::count())->toBe(0);
});

it('does not let another supplier land on someone else\'s payment', function (): void {
    $transaction = paymobTransaction($this->payment);
    $flat = Arr::dot($transaction);
    $query = [
        ...collect($flat)->map(fn (mixed $value): string => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value)->all(),
        'hmac' => $this->gateway->sign($flat),
    ];

    $this->actingAs(User::factory()->supplier()->create())
        ->get(route('supplier.subscription.payment-return').'?'.http_build_query($query))
        ->assertNotFound();

    expect(Subscription::count())->toBe(0);
});

it('expires checkouts that were abandoned', function (): void {
    $this->travel(2)->hours();

    $this->artisan('payments:expire-stale')->assertSuccessful();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Expired);
});

it('never falls back to the fake gateway outside local and testing', function (): void {
    $this->app->forgetInstance(PaymentGateway::class);
    $this->app->detectEnvironment(fn (): string => 'production');
    config(['services.paymob.secret_key' => null]);
    Http::fake();
    $this->withoutMiddleware(PreventRequestForgery::class);

    expect(app(PaymentGateway::class))->toBeInstanceOf(PaymobGateway::class);

    $this->actingAs($this->supplier)
        ->from(route('supplier.subscription.checkout', $this->monthly))
        ->post(route('supplier.subscription.subscribe', $this->monthly))
        ->assertRedirect(route('supplier.subscription.checkout', $this->monthly))
        ->assertSessionHas('status', __('subscription.payment_failed'));

    Http::assertNothingSent();
    expect(Subscription::count())->toBe(0);
});
