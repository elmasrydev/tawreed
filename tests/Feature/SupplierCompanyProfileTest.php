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

it('saves the company details', function (): void {
    $this->actingAs($this->supplier)
        ->put(route('supplier.company.update'), companyPayload($this->supplier))
        ->assertRedirect(route('supplier.account'))
        ->assertSessionHas('status', __('supplier.company_saved'));

    $this->profile->refresh();
    $this->supplier->refresh();

    expect($this->profile->company_name)->toBe('Delta Coffee Roasters')
        ->and($this->profile->facility_address)->toBe('Industrial zone, Obour')
        ->and($this->profile->activity_description)->toBe('Roasting and packing specialty coffee.')
        ->and($this->supplier->name)->toBe('Mahmoud El-Deltawy');
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

it('never lets the supplier change their sign-in phone or email', function (): void {
    $original = $this->supplier->only(['phone', 'email']);

    $this->actingAs($this->supplier)
        ->get(route('supplier.account', ['edit' => 1]))
        ->assertSee(__('supplier.contact_locked_hint'));

    $this->put(route('supplier.company.update'), companyPayload($this->supplier, [
        'phone' => '011 8765 4321',
        'email' => 'new@deltaroasters.eg',
    ]))
        ->assertRedirect(route('supplier.account'))
        ->assertSessionHasNoErrors();

    $this->supplier->refresh();

    expect($this->supplier->only(['phone', 'email']))->toBe($original)
        ->and($this->supplier->hasVerifiedEmail())->toBeTrue();
});

it('rejects empty required fields', function (): void {
    $this->actingAs($this->supplier)
        ->from(route('supplier.account', ['edit' => 1]))
        ->put(route('supplier.company.update'), companyPayload($this->supplier, [
            'company_name' => '',
            'name' => '',
        ]))
        ->assertRedirect(route('supplier.account', ['edit' => 1]))
        ->assertSessionHasErrors(['company_name', 'name']);

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
