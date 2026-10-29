<?php

use App\Models\User;

beforeEach(function (): void {
    seedReferenceData();

    $this->supplier = User::factory()->supplier()->create();
    $this->profile = $this->supplier->supplierProfile;
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function companyPayload(User $supplier, array $overrides = []): array
{
    return [
        'company_name' => 'Delta Coffee Roasters',
        'name' => 'Mahmoud El-Deltawy',
        'phone' => '010 1234 5678',
        'email' => $supplier->email,
        'facility_address' => 'Industrial zone, Obour',
        'activity_description' => 'Roasting and packing specialty coffee.',
        ...$overrides,
    ];
}

it('opens the company form prefilled from the profile', function (): void {
    $this->actingAs($this->supplier)
        ->get(route('supplier.account'))
        ->assertOk()
        ->assertSee(route('supplier.account', ['edit' => 1]), false);

    $this->get(route('supplier.account', ['edit' => 1]))
        ->assertOk()
        ->assertSee(route('supplier.company.update'), false)
        ->assertSee('value="'.e($this->profile->company_name).'"', false)
        ->assertSee('value="'.e($this->supplier->email).'"', false);
});

it('saves the company and contact details', function (): void {
    $this->actingAs($this->supplier)
        ->put(route('supplier.company.update'), companyPayload($this->supplier, ['phone' => '011 8765 4321']))
        ->assertRedirect(route('supplier.account'))
        ->assertSessionHas('status', __('supplier.company_saved'));

    $this->profile->refresh();
    $this->supplier->refresh();

    expect($this->profile->company_name)->toBe('Delta Coffee Roasters')
        ->and($this->profile->facility_address)->toBe('Industrial zone, Obour')
        ->and($this->profile->activity_description)->toBe('Roasting and packing specialty coffee.')
        ->and($this->supplier->name)->toBe('Mahmoud El-Deltawy')
        ->and($this->supplier->phone)->toBe('01187654321')
        ->and($this->supplier->hasVerifiedEmail())->toBeTrue();
});

it('never lets the supplier change the verified registration numbers', function (): void {
    $original = $this->profile->only(['commercial_reg_no', 'tax_number']);

    $this->actingAs($this->supplier)
        ->put(route('supplier.company.update'), companyPayload($this->supplier, [
            'commercial_reg_no' => '999999',
            'tax_number' => '999-999-999',
        ]))
        ->assertRedirect(route('supplier.account'));

    expect($this->profile->fresh()->only(['commercial_reg_no', 'tax_number']))->toBe($original);
});

it('asks the supplier to confirm a new email address', function (): void {
    config(['auth.otp.enabled' => true]);

    $this->actingAs($this->supplier)
        ->put(route('supplier.company.update'), companyPayload($this->supplier, ['email' => 'new@deltaroasters.eg']))
        ->assertRedirect(route('email.verify'));

    $this->supplier->refresh();

    expect($this->supplier->email)->toBe('new@deltaroasters.eg')
        ->and($this->supplier->hasVerifiedEmail())->toBeFalse();
});

it('rejects empty required fields, a bad phone, and a taken email', function (): void {
    $other = User::factory()->buyer()->create();

    $this->actingAs($this->supplier)
        ->from(route('supplier.account', ['edit' => 1]))
        ->put(route('supplier.company.update'), companyPayload($this->supplier, [
            'company_name' => '',
            'name' => '',
            'phone' => '12345',
            'email' => $other->email,
        ]))
        ->assertRedirect(route('supplier.account', ['edit' => 1]))
        ->assertSessionHasErrors(['company_name', 'name', 'phone', 'email']);

    expect($this->profile->fresh()->company_name)->not->toBe('');
});

it('only updates the signed-in supplier\'s own profile', function (): void {
    $otherSupplier = User::factory()->supplier()->create();
    $otherName = $otherSupplier->supplierProfile->company_name;

    $this->actingAs($this->supplier)
        ->put(route('supplier.company.update'), companyPayload($this->supplier, [
            'user_id' => $otherSupplier->id,
            'supplier_profile_id' => $otherSupplier->supplierProfile->id,
        ]));

    expect($otherSupplier->supplierProfile->fresh()->company_name)->toBe($otherName)
        ->and($this->profile->fresh()->company_name)->toBe('Delta Coffee Roasters');
});

it('keeps buyers and guests out of the supplier company form', function (): void {
    $this->actingAs(User::factory()->buyer()->create())
        ->put(route('supplier.company.update'), companyPayload($this->supplier))
        ->assertRedirect(route('buyer.home'));

    expect($this->profile->fresh()->company_name)->not->toBe('Delta Coffee Roasters');

    auth()->logout();

    $this->put(route('supplier.company.update'), companyPayload($this->supplier))
        ->assertRedirect(route('login'));
});
