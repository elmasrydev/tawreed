<?php

use App\Enums\QuoteStatus;
use App\Enums\RfqStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\VatMode;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\Subscription;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function (): void {
    seedReferenceData();
});

it('creates a buyer with an anonymous label of business type and governorate', function (): void {
    $buyer = User::factory()->buyer()->create();

    expect($buyer->role)->toBe(UserRole::Buyer)
        ->and($buyer->isBuyer())->toBeTrue()
        ->and($buyer->buyerProfile)->not->toBeNull();

    $label = $buyer->buyerProfile->anonymousLabel();

    expect($label)->toContain('—')
        ->and($label)->not->toContain($buyer->buyerProfile->company_name);
});

it('only lets verified suppliers submit quotes', function (): void {
    $verified = User::factory()->supplier()->create();
    $pending = User::factory()
        ->has(SupplierProfile::factory()->pending(), 'supplierProfile')
        ->create(['role' => UserRole::Supplier]);

    expect($verified->supplierProfile->canSubmitQuotes())->toBeTrue()
        ->and($pending->supplierProfile->canSubmitQuotes())->toBeFalse()
        ->and($pending->supplierProfile->verification_status)->toBe(VerificationStatus::Pending);
});

it('lists only open requests whose deadline has not passed in the supplier feed', function (): void {
    $open = Rfq::factory()->create();
    Rfq::factory()->draft()->create();
    Rfq::factory()->expired()->create();

    $browsable = Rfq::query()->browsable()->pluck('id');

    expect($browsable)->toHaveCount(1)
        ->and($browsable->first())->toBe($open->id);
});

it('flags a request as ending soon within two days of its deadline', function (): void {
    expect(Rfq::factory()->endingSoon()->create()->isEndingSoon())->toBeTrue()
        ->and(Rfq::factory()->create(['quote_deadline' => now()->addWeeks(2)])->isEndingSoon())->toBeFalse();
});

it('prevents a supplier from quoting the same request twice', function (): void {
    $rfq = Rfq::factory()->create();
    $supplier = User::factory()->supplier()->create();

    Quote::factory()->for($rfq)->create(['supplier_id' => $supplier->id]);

    expect(fn () => Quote::factory()->for($rfq)->create(['supplier_id' => $supplier->id]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('adds vat and delivery to the grand total only when vat is excluded', function (): void {
    $included = Quote::factory()->create([
        'total_price' => 90000,
        'vat_mode' => VatMode::Included,
        'delivery_cost' => 0,
    ]);

    $excluded = Quote::factory()->create([
        'total_price' => 90000,
        'vat_mode' => VatMode::Excluded,
        'vat_amount' => 12600,
        'delivery_cost' => 1500,
    ]);

    expect($included->grandTotal())->toBe(90000.0)
        ->and($excluded->grandTotal())->toBe(104100.0);
});

it('treats a supplier as unlocked only while a subscription is current', function (): void {
    $subscribed = User::factory()->supplier()->create();
    Subscription::factory()->create(['supplier_id' => $subscribed->id]);

    $lapsed = User::factory()->supplier()->create();
    Subscription::factory()->expired()->create(['supplier_id' => $lapsed->id]);

    expect($subscribed->hasActiveSubscription())->toBeTrue()
        ->and($lapsed->hasActiveSubscription())->toBeFalse()
        ->and($lapsed->activeSubscription())->toBeNull();
});

it('warns a supplier when the plan ends within a week', function (): void {
    expect(Subscription::factory()->expiringSoon()->create()->isExpiringSoon())->toBeTrue()
        ->and(Subscription::factory()->create()->isExpiringSoon())->toBeFalse();
});

it('counts a trial subscription as unlocking the platform', function (): void {
    $supplier = User::factory()->supplier()->create();
    $trial = Subscription::factory()->trial()->create(['supplier_id' => $supplier->id]);

    expect($trial->status)->toBe(SubscriptionStatus::Trial)
        ->and($trial->isCurrent())->toBeTrue()
        ->and($supplier->hasActiveSubscription())->toBeTrue();
});

it('links a conversation to both sides of the selected quote', function (): void {
    $conversation = Conversation::factory()->create();
    $buyer = $conversation->buyer;
    $supplier = $conversation->supplier;

    expect($conversation->isParticipant($buyer))->toBeTrue()
        ->and($conversation->isParticipant($supplier))->toBeTrue()
        ->and($conversation->counterpartFor($buyer)->id)->toBe($supplier->id)
        ->and($conversation->counterpartFor($supplier)->id)->toBe($buyer->id)
        ->and($conversation->quote->status)->toBe(QuoteStatus::Selected);
});

it('keeps an outsider out of a conversation', function (): void {
    $conversation = Conversation::factory()->create();

    expect($conversation->isParticipant(User::factory()->buyer()->create()))->toBeFalse();
});

it('admits only admins to the filament panel', function (): void {
    $panel = Filament\Facades\Filament::getPanel('admin');

    expect(User::factory()->admin()->create()->canAccessPanel($panel))->toBeTrue()
        ->and(User::factory()->buyer()->create()->canAccessPanel($panel))->toBeFalse()
        ->and(User::factory()->admin()->suspended()->create()->canAccessPanel($panel))->toBeFalse();
});

it('resolves reference names in the active locale', function (): void {
    $category = Category::where('slug', 'packaging')->firstOrFail();

    app()->setLocale('ar');
    expect($category->refresh()->name)->toBe('تغليف');

    app()->setLocale('en');
    expect($category->refresh()->name)->toBe('Packaging');
});

it('exposes rfq status behaviour through the enum', function (): void {
    expect(RfqStatus::Open->acceptsQuotes())->toBeTrue()
        ->and(RfqStatus::Awarded->acceptsQuotes())->toBeFalse()
        ->and(QuoteStatus::Pending->isActionable())->toBeTrue()
        ->and(QuoteStatus::Selected->isActionable())->toBeFalse();
});
