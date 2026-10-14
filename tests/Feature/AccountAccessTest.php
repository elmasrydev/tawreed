<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Filament\Resources\SupplierProfiles\Pages\EditSupplierProfile;
use App\Filament\Widgets\VerificationQueue;
use App\Models\Rfq;
use App\Models\Subscription;
use App\Models\SupplierProfile;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();

    $this->admin = User::factory()->admin()->create();
});

function supplierWithStatus(VerificationStatus $status): User
{
    return User::factory()
        ->has(SupplierProfile::factory()->state(['verification_status' => $status]), 'supplierProfile')
        ->create(['role' => UserRole::Supplier, 'email' => strtolower($status->value).'@supplier.test']);
}

it('shows approve, reject and suspend as buttons in the verification queue', function (): void {
    $profile = supplierWithStatus(VerificationStatus::Pending)->supplierProfile;

    Livewire::actingAs($this->admin)
        ->test(VerificationQueue::class)
        ->assertActionVisible(TestAction::make('approve')->table($profile))
        ->assertActionVisible(TestAction::make('reject')->table($profile))
        ->assertActionVisible(TestAction::make('suspend')->table($profile))
        ->assertActionHidden(TestAction::make('reactivate')->table($profile));
});

it('offers approve and reject on the supplier page only while under review', function (): void {
    $pending = supplierWithStatus(VerificationStatus::Pending)->supplierProfile;
    $verified = supplierWithStatus(VerificationStatus::Verified)->supplierProfile;

    Livewire::actingAs($this->admin)
        ->test(EditSupplierProfile::class, ['record' => $pending->getRouteKey()])
        ->assertActionVisible('approve')
        ->assertActionVisible('reject')
        ->assertActionVisible('suspend');

    Livewire::actingAs($this->admin)
        ->test(EditSupplierProfile::class, ['record' => $verified->getRouteKey()])
        ->assertActionHidden('approve')
        ->assertActionHidden('reject')
        ->assertActionVisible('suspend');
});

it('locks a suspended supplier out and lets them back in when reactivated', function (): void {
    $supplier = supplierWithStatus(VerificationStatus::Verified);

    Livewire::actingAs($this->admin)
        ->test(EditSupplierProfile::class, ['record' => $supplier->supplierProfile->getRouteKey()])
        ->callAction('suspend', data: ['note' => 'Repeated complaints.']);

    expect($supplier->fresh()->status)->toBe(UserStatus::Suspended)
        ->and($supplier->supplierProfile->fresh()->verification_status)->toBe(VerificationStatus::Suspended);

    auth()->logout();

    $this->post(route('login'), ['login' => $supplier->email, 'password' => 'password'])
        ->assertSessionHasErrors(['login' => __('auth.suspended')]);
    $this->assertGuest();

    Livewire::actingAs($this->admin)
        ->test(EditSupplierProfile::class, ['record' => $supplier->supplierProfile->getRouteKey()])
        ->assertActionHidden('suspend')
        ->callAction('reactivate');

    auth()->logout();

    expect($supplier->fresh()->status)->toBe(UserStatus::Active);

    $this->post(route('login'), ['login' => $supplier->email, 'password' => 'password'])
        ->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($supplier->fresh());
});

it('tells a suspended account why it cannot sign in, but only with the right password', function (): void {
    $buyer = User::factory()->buyer()->suspended()->create(['email' => 'blocked@cafecity.eg']);

    $this->post(route('login'), ['login' => 'blocked@cafecity.eg', 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['login' => __('auth.failed')]);

    $this->post(route('login'), ['login' => 'blocked@cafecity.eg', 'password' => 'password'])
        ->assertSessionHasErrors(['login' => __('auth.suspended')]);

    $this->assertGuest();
});

it('explains why quoting is locked for each account state', function (VerificationStatus $status, bool $subscribed, ?string $message): void {
    $supplier = supplierWithStatus($status);

    if ($subscribed) {
        Subscription::factory()->create(['supplier_id' => $supplier->id]);
    }

    $rfq = Rfq::factory()->create();
    $response = $this->actingAs($supplier)->get(route('supplier.rfqs.show', $rfq))->assertOk();

    if ($message === null) {
        $response->assertSeeLivewire('supplier.submit-quote');
    } else {
        $response->assertSee(__($message))->assertDontSeeLivewire('supplier.submit-quote');
    }
})->with([
    'under review' => [VerificationStatus::Pending, true, 'supplier.gate_review'],
    'rejected' => [VerificationStatus::Rejected, true, 'supplier.gate_rejected'],
    'verified without a plan' => [VerificationStatus::Verified, false, 'supplier.gate_subscription'],
    'verified and subscribed' => [VerificationStatus::Verified, true, null],
]);

it('lets a supplier without a subscription sign in and browse requests', function (): void {
    $supplier = supplierWithStatus(VerificationStatus::Verified);

    $this->post(route('login'), ['login' => $supplier->email, 'password' => 'password'])
        ->assertRedirect(route('supplier.dashboard'));

    $this->get(route('supplier.feed'))
        ->assertOk()
        ->assertSee(__('supplier.gate_subscription'))
        ->assertSee(route('supplier.subscription'), false);
});

it('words every access message in both languages', function (): void {
    foreach (['en', 'ar'] as $locale) {
        foreach (['supplier.gate_review', 'supplier.gate_rejected', 'supplier.gate_suspended', 'supplier.gate_subscription', 'auth.suspended'] as $key) {
            expect(__($key, [], $locale))->not->toBe($key);
        }
    }
});
