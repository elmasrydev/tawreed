<?php

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\SupplierProfiles\Pages\ListSupplierProfiles;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Widgets\MarketplaceStats;
use App\Filament\Widgets\VerificationQueue;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Report;
use App\Models\Rfq;
use App\Models\Subscription;
use App\Models\SupplierProfile;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();

    $this->admin = User::factory()->admin()->create();
});

it('signs an admin in through the panel login form', function (): void {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.test',
        'password' => 'password',
    ]);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'admin@example.test', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($admin);
});

it('rejects an admin sign-in that uses the wrong password', function (): void {
    User::factory()->admin()->create(['email' => 'admin@example.test', 'password' => 'password']);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'admin@example.test', 'password' => '01000000000'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

it('lets only an admin open the panel', function (): void {
    $this->actingAs($this->admin)->get('/admin')->assertSuccessful();

    $this->actingAs(User::factory()->buyer()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->supplier()->create())->get('/admin')->assertForbidden();
});

it('lists suppliers awaiting review in the queue', function (): void {
    $pending = User::factory()
        ->has(SupplierProfile::factory()->pending()->state(['company_name' => 'Mahalla Textile']), 'supplierProfile')
        ->create(['role' => UserRole::Supplier]);

    User::factory()->supplier()->create();

    Livewire::actingAs($this->admin)
        ->test(VerificationQueue::class)
        ->assertCanSeeTableRecords([$pending->supplierProfile])
        ->assertSee('Mahalla Textile');
});

it('approves a supplier and grants the verification badge', function (): void {
    $supplier = User::factory()
        ->has(SupplierProfile::factory()->pending(), 'supplierProfile')
        ->create(['role' => UserRole::Supplier]);

    $profile = $supplier->supplierProfile;

    Livewire::actingAs($this->admin)
        ->test(VerificationQueue::class)
        ->callAction(TestAction::make('approve')->table($profile));

    $profile->refresh();

    expect($profile->verification_status)->toBe(VerificationStatus::Verified)
        ->and($profile->verified_at)->not->toBeNull()
        ->and($profile->verified_by)->toBe($this->admin->id)
        ->and($profile->canSubmitQuotes())->toBeTrue();
});

it('rejects a supplier with a note they will see', function (): void {
    $profile = User::factory()
        ->has(SupplierProfile::factory()->pending(), 'supplierProfile')
        ->create(['role' => UserRole::Supplier])
        ->supplierProfile;

    Livewire::actingAs($this->admin)
        ->test(ListSupplierProfiles::class)
        ->callAction(
            TestAction::make('reject')->table($profile),
            data: ['note' => 'Commercial registration is unreadable.'],
        );

    $profile->refresh();

    expect($profile->verification_status)->toBe(VerificationStatus::Rejected)
        ->and($profile->verification_note)->toBe('Commercial registration is unreadable.')
        ->and($profile->verified_at)->toBeNull()
        ->and($profile->canSubmitQuotes())->toBeFalse();
});

it('suspends and reactivates a user account', function (): void {
    $buyer = User::factory()->buyer()->create();

    $component = Livewire::actingAs($this->admin)
        ->test(ListUsers::class)
        ->callAction(TestAction::make('toggleSuspension')->table($buyer));

    expect($buyer->fresh()->status)->toBe(UserStatus::Suspended);

    $component->callAction(TestAction::make('toggleSuspension')->table($buyer->fresh()));

    expect($buyer->fresh()->status)->toBe(UserStatus::Active);
});

it('finds a user by phone or commercial registration', function (): void {
    $supplier = User::factory()->supplier()->create(['phone' => '01155667788']);
    $supplier->supplierProfile->update(['commercial_reg_no' => '998877']);
    $other = User::factory()->buyer()->create();

    Livewire::actingAs($this->admin)
        ->test(ListUsers::class)
        ->searchTable('01155667788')
        ->assertCanSeeTableRecords([$supplier])
        ->assertCanNotSeeTableRecords([$other])
        ->searchTable('998877')
        ->assertCanSeeTableRecords([$supplier]);
});

it('removes reported content and closes the report', function (): void {
    $quote = Quote::factory()->create();

    $report = Report::factory()->create([
        'reportable_type' => Quote::class,
        'reportable_id' => $quote->id,
        'type' => ReportType::ContactDetailsBypass,
        'status' => ReportStatus::Open,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListReports::class)
        ->callAction(TestAction::make('remove')->table($report), data: ['note' => 'Contact details inside the quote.']);

    $report->refresh();

    expect($report->status)->toBe(ReportStatus::Removed)
        ->and($report->resolved_by)->toBe($this->admin->id)
        ->and($report->resolved_at)->not->toBeNull()
        ->and(Quote::find($quote->id))->toBeNull()
        ->and(Quote::withTrashed()->find($quote->id))->not->toBeNull();
});

it('dismisses a report without touching the content', function (): void {
    $quote = Quote::factory()->create();
    $report = Report::factory()->create([
        'reportable_type' => Quote::class,
        'reportable_id' => $quote->id,
        'status' => ReportStatus::Open,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListReports::class)
        ->callAction(TestAction::make('dismiss')->table($report));

    expect($report->fresh()->status)->toBe(ReportStatus::Dismissed)
        ->and(Quote::find($quote->id))->not->toBeNull();
});

it('extends a subscription and issues an invoice', function (): void {
    $subscription = Subscription::factory()->create(['ends_at' => now()->addDays(3)]);
    $endsBefore = $subscription->ends_at;

    Livewire::actingAs($this->admin)
        ->test(ListSubscriptions::class)
        ->callAction(TestAction::make('extend')->table($subscription), data: ['days' => 30]);

    $subscription->refresh();

    expect($subscription->ends_at->greaterThan($endsBefore))->toBeTrue()
        ->and($subscription->ends_at->toDateString())->toBe($endsBefore->copy()->addDays(30)->toDateString())
        ->and($subscription->invoices()->count())->toBe(1);
});

it('reports the marketplace headline numbers', function (): void {
    Rfq::factory()->count(3)->create();
    Quote::factory()->count(2)->create();
    $subscription = Subscription::factory()->create();
    Invoice::factory()->create([
        'subscription_id' => $subscription->id,
        'supplier_id' => $subscription->supplier_id,
        'total_egp' => 285,
        'issued_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(MarketplaceStats::class)
        ->assertSee(__('admin.active_rfqs'))
        ->assertSee(__('admin.monthly_revenue'))
        ->assertSee('285.00');
});

it('renders the panel with the TawreedHub theme, brand and design navigation', function (): void {
    User::factory()
        ->has(SupplierProfile::factory()->pending(), 'supplierProfile')
        ->create(['role' => UserRole::Supplier]);

    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('images/brand/logo-white.png', escape: false)
        ->assertSee(__('admin.admin_badge'))
        ->assertSee(__('admin.overview'))
        ->assertSee(__('admin.verification_queue'))
        ->assertSee('supplier-profiles?tab=pending', escape: false);

    expect(filament()->getPanel('admin')->getViteTheme())->toBe('resources/css/filament/admin/theme.css');
});

it('shows the admin panel in the admin user\'s language', function (): void {
    $this->admin->update(['locale' => 'en']);

    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('dir="ltr"', escape: false)
        ->assertSee(__('admin.overview', [], 'en'));
});

it('brands the admin sign-in page', function (): void {
    $this->get('/admin/login')
        ->assertSuccessful()
        ->assertSee('images/brand/logo-full-color.png', escape: false);
});
