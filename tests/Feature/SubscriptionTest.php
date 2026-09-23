<?php

use App\Actions\Subscription\SubscribeToPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\User;

beforeEach(function (): void {
    seedReferenceData();

    $this->supplier = User::factory()->supplier()->create();
    $this->monthly = Plan::where('slug', 'monthly')->firstOrFail();
    $this->yearly = Plan::where('slug', 'yearly')->firstOrFail();
});

it('starts a plan and issues an invoice with egyptian vat', function (): void {
    $this->actingAs($this->supplier)
        ->post(route('supplier.subscription.subscribe', $this->monthly))
        ->assertRedirect(route('supplier.subscription'));

    $subscription = Subscription::sole();
    $invoice = Invoice::sole();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->plan_id)->toBe($this->monthly->id)
        ->and($subscription->payment_ref)->toStartWith('DEMO-')
        ->and($subscription->ends_at->diffInDays($subscription->starts_at, absolute: true))->toBe(30.0)
        ->and($this->supplier->fresh()->hasActiveSubscription())->toBeTrue();

    expect($invoice->number)->toBe('INV-'.now()->year.'-00001')
        ->and((float) $invoice->amount_egp)->toBe(250.0)
        ->and((float) $invoice->vat_egp)->toBe(35.0)
        ->and((float) $invoice->total_egp)->toBe(285.0);
});

it('gives the free trial only on a first ever subscription', function (): void {
    $first = app(SubscribeToPlan::class)->handle($this->supplier, $this->yearly);

    expect($first->ends_at->diffInDays($first->starts_at, absolute: true))
        ->toBe((float) ($this->yearly->days + $this->yearly->trial_days));

    $second = app(SubscribeToPlan::class)->handle($this->supplier->fresh(), $this->yearly);

    expect($second->ends_at->diffInDays($second->starts_at, absolute: true))
        ->toBe((float) $this->yearly->days);
});

it('extends from the end of a running plan rather than discarding it', function (): void {
    $current = Subscription::factory()->create([
        'supplier_id' => $this->supplier->id,
        'ends_at' => now()->addDays(20),
    ]);

    $next = app(SubscribeToPlan::class)->handle($this->supplier->fresh(), $this->monthly);

    expect($next->starts_at->toDateString())->toBe($current->ends_at->toDateString());
});

it('numbers invoices sequentially', function (): void {
    app(SubscribeToPlan::class)->handle($this->supplier, $this->monthly);
    app(SubscribeToPlan::class)->handle($this->supplier->fresh(), $this->monthly);

    expect(Invoice::orderBy('id')->pluck('number')->all())
        ->toBe(['INV-'.now()->year.'-00001', 'INV-'.now()->year.'-00002']);
});

it('sends a lapsed supplier to the plans page instead of the chats', function (): void {
    $this->actingAs($this->supplier)
        ->get(route('supplier.chats.index'))
        ->assertRedirect(route('supplier.subscription'));
});

it('lets a subscribed supplier reach the chats', function (): void {
    Subscription::factory()->create(['supplier_id' => $this->supplier->id]);

    $this->actingAs($this->supplier->fresh())
        ->get(route('supplier.chats.index'))
        ->assertOk();
});

it('keeps quotes and history when a plan lapses', function (): void {
    Subscription::factory()->expired()->create(['supplier_id' => $this->supplier->id]);
    Quote::factory()->create(['supplier_id' => $this->supplier->id]);

    expect($this->supplier->fresh()->hasActiveSubscription())->toBeFalse();

    $this->actingAs($this->supplier)
        ->get(route('supplier.quotes.index'))
        ->assertOk();

    expect($this->supplier->quotes()->count())->toBe(1);
});

it('toggles auto renew on the running plan', function (): void {
    $subscription = Subscription::factory()->create([
        'supplier_id' => $this->supplier->id,
        'auto_renew' => false,
    ]);

    $this->actingAs($this->supplier->fresh())
        ->from(route('supplier.subscription'))
        ->patch(route('supplier.subscription.auto-renew'))
        ->assertRedirect(route('supplier.subscription'));

    expect($subscription->fresh()->auto_renew)->toBeTrue();
});

it('downloads an invoice as a pdf', function (): void {
    app(SubscribeToPlan::class)->handle($this->supplier, $this->monthly);
    $invoice = Invoice::sole();

    $response = $this->actingAs($this->supplier->fresh())
        ->get(route('supplier.invoices.show', $invoice));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('refuses an invoice belonging to another supplier', function (): void {
    app(SubscribeToPlan::class)->handle($this->supplier, $this->monthly);

    $this->actingAs(User::factory()->supplier()->create())
        ->get(route('supplier.invoices.show', Invoice::sole()))
        ->assertForbidden();
});

it('shows the plans with the best value marked', function (): void {
    $this->actingAs($this->supplier)
        ->get(route('supplier.subscription'))
        ->assertOk()
        ->assertSee(__('subscription.no_active_title'))
        ->assertSee(__('subscription.best_value'))
        ->assertSee('2,500');
});
