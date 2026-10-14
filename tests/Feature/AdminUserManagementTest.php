<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Filament\Resources\SupplierProfiles\Pages\EditSupplierProfile;
use App\Filament\Resources\SupplierProfiles\Pages\ViewSupplierProfile;
use App\Filament\Resources\SupplierProfiles\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();

    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

/**
 * @return array<string, mixed>
 */
function accountFields(array $overrides = []): array
{
    return [
        'name' => 'Mona Adel',
        'email' => 'mona@example.test',
        'phone' => '010 1111 2222',
        'password' => 'Password!234',
        'status' => UserStatus::Active->value,
        ...$overrides,
    ];
}

it('creates a verified buyer with their company profile', function (): void {
    Notification::fake();

    Livewire::test(CreateUser::class)
        ->fillForm(accountFields([
            'role' => UserRole::Buyer->value,
            'buyer' => [
                'company_name' => 'Nile Hotel',
                'business_type_id' => BusinessType::query()->value('id'),
                'governorate_id' => Governorate::query()->value('id'),
            ],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $buyer = User::where('email', 'mona@example.test')->firstOrFail();

    expect($buyer->role)->toBe(UserRole::Buyer)
        ->and($buyer->phone)->toBe('01011112222')
        ->and($buyer->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('Password!234', $buyer->password))->toBeTrue()
        ->and($buyer->buyerProfile->company_name)->toBe('Nile Hotel');

    Notification::assertNothingSent();
});

it('creates a supplier with coverage, categories and a recorded verification', function (): void {
    $governorates = Governorate::query()->take(2)->pluck('id')->all();
    $category = Category::query()->main()->value('id');

    Livewire::test(CreateUser::class)
        ->fillForm(accountFields([
            'role' => UserRole::Supplier->value,
            'supplier' => [
                'company_name' => 'Delta Packaging',
                'commercial_reg_no' => 'CR-123',
                'tax_number' => 'TX-456',
                'governorate_ids' => $governorates,
                'category_ids' => [$category],
                'verification_status' => VerificationStatus::Verified->value,
            ],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $profile = User::where('email', 'mona@example.test')->firstOrFail()->supplierProfile;

    expect($profile->company_name)->toBe('Delta Packaging')
        ->and($profile->governorates->pluck('id')->sort()->values()->all())->toBe(collect($governorates)->sort()->values()->all())
        ->and($profile->categories->pluck('id')->all())->toBe([$category])
        ->and($profile->isVerified())->toBeTrue()
        ->and($profile->verified_by)->toBe($this->admin->id);
});

it('rejects a duplicate or malformed phone number', function (): void {
    User::factory()->create(['phone' => '01011112222']);

    Livewire::test(CreateUser::class)
        ->fillForm(accountFields(['role' => UserRole::Admin->value]))
        ->call('create')
        ->assertHasFormErrors(['phone']);

    Livewire::test(CreateUser::class)
        ->fillForm(accountFields(['role' => UserRole::Admin->value, 'phone' => '0999']))
        ->call('create')
        ->assertHasFormErrors(['phone']);
});

it('edits an account and its buyer profile, keeping the password when left empty', function (): void {
    $buyer = User::factory()->buyer()->create(['phone' => '01033334444']);
    $password = $buyer->password;

    Livewire::test(EditUser::class, ['record' => $buyer->getRouteKey()])
        ->assertSchemaStateSet(['buyer.company_name' => $buyer->buyerProfile->company_name])
        ->fillForm([
            'name' => 'Renamed Buyer',
            'password' => '',
            'status' => UserStatus::Suspended->value,
            'buyer.company_name' => 'Renamed Company',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $buyer->refresh();

    expect($buyer->name)->toBe('Renamed Buyer')
        ->and($buyer->password)->toBe($password)
        ->and($buyer->isSuspended())->toBeTrue()
        ->and($buyer->buyerProfile->company_name)->toBe('Renamed Company');
});

it('does not let an admin change their own status', function (): void {
    $this->admin->update(['phone' => '01255556666']);

    Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->assertFormFieldDisabled('status')
        ->fillForm(['name' => 'Chief Admin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->admin->fresh()->status)->toBe(UserStatus::Active)
        ->and($this->admin->fresh()->name)->toBe('Chief Admin');
});

it('edits a supplier profile including coverage areas', function (): void {
    $profile = User::factory()->supplier()->create()->supplierProfile;
    $governorate = Governorate::query()->orderByDesc('id')->value('id');

    Livewire::test(EditSupplierProfile::class, ['record' => $profile->getRouteKey()])
        ->fillForm([
            'company_name' => 'Updated Supplies Co',
            'governorates' => [$governorate],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $profile->refresh();

    expect($profile->company_name)->toBe('Updated Supplies Co')
        ->and($profile->governorates->pluck('id')->all())->toBe([$governorate]);
});

it('lets an admin upload a document for a supplier', function (): void {
    Storage::fake(config('filesystems.documents_disk'));
    $profile = User::factory()->supplier()->create()->supplierProfile;

    Livewire::test(DocumentsRelationManager::class, ['ownerRecord' => $profile, 'pageClass' => ViewSupplierProfile::class])
        ->callAction(TestAction::make('upload')->table(), data: [
            'type' => 'tax_card',
            'file' => UploadedFile::fake()->create('tax.pdf', 100, 'application/pdf'),
        ])
        ->assertHasNoFormErrors();

    expect($profile->documents()->sole()->hasMedia('file'))->toBeTrue();
});

it('opens the create, edit and supplier edit pages', function (): void {
    $supplier = User::factory()->supplier()->create();

    $this->get(CreateUser::getUrl())->assertOk();
    $this->get(CreateUser::getUrl(['role' => 'supplier']))->assertOk()->assertSee(__('ui.tax_number'));
    $this->get(EditUser::getUrl(['record' => $supplier]))->assertOk()->assertSee(__('admin.edit_supplier_profile'));
    $this->get(EditSupplierProfile::getUrl(['record' => $supplier->supplierProfile]))->assertOk();
});
