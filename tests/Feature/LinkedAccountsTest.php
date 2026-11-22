<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Rfq;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();

    $this->buyer = User::factory()->buyer()->create([
        'email' => 'ahmed@cafecity.eg',
        'phone' => '01023456789',
        'password' => 'BuyerPass!234',
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function linkedSupplierPayload(array $overrides = []): array
{
    return [
        'name' => 'Ahmed Samy',
        'phone' => '010 2345 6789',
        'email' => 'ahmed@cafecity.eg',
        'password' => 'SupplierPass!234',
        'password_confirmation' => 'SupplierPass!234',
        'company_name' => 'Cafe City Roasters',
        'commercial_reg_no' => '445210',
        'tax_number' => '204-881-337',
        'governorate_ids' => [Governorate::query()->value('id')],
        'category_ids' => [Category::query()->main()->value('id')],
        'documents' => [
            'commercial_registration' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
            'tax_card' => UploadedFile::fake()->create('tax.pdf', 100, 'application/pdf'),
        ],
        ...$overrides,
    ];
}

it('lets a buyer register a supplier account with the same email and phone', function (): void {
    Storage::fake('public');

    $this->post(route('register.supplier'), linkedSupplierPayload())
        ->assertSessionHasNoErrors();

    $accounts = User::query()->where('email', 'ahmed@cafecity.eg')->orderBy('id')->get();

    expect($accounts->pluck('role')->all())->toBe([UserRole::Buyer, UserRole::Supplier])
        ->and($accounts->pluck('phone')->unique()->all())->toBe(['01023456789']);
});

it('refuses the same password as the linked account', function (): void {
    $this->post(route('register.supplier'), linkedSupplierPayload([
        'password' => 'BuyerPass!234',
        'password_confirmation' => 'BuyerPass!234',
    ]))->assertSessionHasErrors(['password' => __('auth.password_matches_linked_account')]);

    expect(User::query()->where('email', 'ahmed@cafecity.eg')->count())->toBe(1);
});

it('refuses a reused password when only the phone is shared', function (): void {
    $this->post(route('register.supplier'), linkedSupplierPayload([
        'email' => 'other@cafecity.eg',
        'password' => 'BuyerPass!234',
        'password_confirmation' => 'BuyerPass!234',
    ]))->assertSessionHasErrors('password');
});

it('still refuses a second account with the same role', function (): void {
    $this->post(route('register.buyer'), [
        'name' => 'Ahmed Samy',
        'phone' => '010 2345 6789',
        'email' => 'ahmed@cafecity.eg',
        'password' => 'Another!234',
        'password_confirmation' => 'Another!234',
        'company_name' => 'Cafe City',
        'business_type_id' => BusinessType::query()->value('id'),
        'governorate_id' => Governorate::query()->value('id'),
    ])->assertSessionHasErrors(['email', 'phone']);
});

it('keeps admin contact details reserved for the admin', function (): void {
    User::factory()->admin()->create(['email' => 'admin@tawreedhub.net', 'phone' => '01299998888']);

    $this->post(route('register.supplier'), linkedSupplierPayload([
        'email' => 'admin@tawreedhub.net',
        'phone' => '012 9999 8888',
    ]))->assertSessionHasErrors(['email', 'phone']);
});

it('signs into the account whose password matches', function (): void {
    $supplier = User::factory()->supplier()->create([
        'email' => 'ahmed@cafecity.eg',
        'phone' => '01023456789',
        'password' => 'SupplierPass!234',
    ]);

    $this->post(route('login'), ['login' => 'ahmed@cafecity.eg', 'password' => 'SupplierPass!234'])
        ->assertRedirect(route('supplier.dashboard'));
    $this->assertAuthenticatedAs($supplier);

    auth()->logout();

    $this->post(route('login'), ['login' => '010 2345 6789', 'password' => 'BuyerPass!234'])
        ->assertRedirect(route('buyer.home'));
    $this->assertAuthenticatedAs($this->buyer);

    auth()->logout();

    $this->post(route('login'), ['login' => 'ahmed@cafecity.eg', 'password' => 'wrong-password'])
        ->assertSessionHasErrors('login');
    $this->assertGuest();
});

it('never lets a supplier quote on or see a request from their own buyer account', function (): void {
    $supplier = User::factory()->supplier()->create(['email' => 'ahmed@cafecity.eg', 'phone' => '01055550000']);
    Subscription::factory()->create(['supplier_id' => $supplier->id]);

    $ownRfq = Rfq::factory()->for($this->buyer, 'buyer')->create(['title' => 'My own coffee request']);
    $otherRfq = Rfq::factory()->create(['title' => 'Someone else request']);

    expect(Gate::forUser($supplier)->allows('quote', $ownRfq))->toBeFalse()
        ->and(Gate::forUser($supplier)->allows('quote', $otherRfq))->toBeTrue();

    $this->actingAs($supplier)
        ->get(route('supplier.feed'))
        ->assertOk()
        ->assertSee('Someone else request')
        ->assertDontSee('My own coffee request');

    $this->get(route('supplier.rfqs.show', $ownRfq))
        ->assertOk()
        ->assertSee(__('supplier.gate_own_request'));
});

it('shows the admin which accounts share an email or phone', function (): void {
    $supplier = User::factory()->supplier()->create(['email' => 'ahmed@cafecity.eg', 'phone' => '01055550000']);
    $loner = User::factory()->buyer()->create();

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ListUsers::class)
        ->assertTableColumnStateSet('linked_account', UserRole::Supplier->label(), $this->buyer)
        ->assertTableColumnStateSet('linked_account', UserRole::Buyer->label(), $supplier)
        ->assertTableColumnStateNotSet('linked_account', UserRole::Buyer->label(), $loner);
});

it('lets the admin create the linked account but not with the same password', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    $fields = [
        'name' => 'Ahmed Samy',
        'email' => 'ahmed@cafecity.eg',
        'phone' => '010 2345 6789',
        'status' => UserStatus::Active->value,
        'role' => UserRole::Supplier->value,
        'supplier' => [
            'company_name' => 'Cafe City Roasters',
            'commercial_reg_no' => '445210',
            'tax_number' => '204-881-337',
            'governorate_ids' => [Governorate::query()->value('id')],
        ],
    ];

    Livewire::test(CreateUser::class)
        ->fillForm([...$fields, 'password' => 'BuyerPass!234'])
        ->call('create')
        ->assertHasFormErrors(['password'])
        ->assertHasNoFormErrors(['email', 'phone']);

    Livewire::test(CreateUser::class)
        ->fillForm([...$fields, 'role' => UserRole::Buyer->value, 'password' => 'Other!2345'])
        ->call('create')
        ->assertHasFormErrors(['email', 'phone']);
});
