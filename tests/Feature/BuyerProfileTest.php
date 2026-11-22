<?php

use App\Models\BusinessType;
use App\Models\Governorate;
use App\Models\User;

beforeEach(function (): void {
    seedReferenceData();

    $this->buyer = User::factory()->buyer()->create();
    $this->profile = $this->buyer->buyerProfile;
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function buyerProfilePayload(array $overrides = []): array
{
    return [
        'name' => 'Nour Hassan',
        'job_title' => 'Procurement lead',
        'company_name' => 'Nile Hotels Group',
        'company_address' => '12 Corniche, Maadi',
        'business_type_id' => BusinessType::query()->latest('id')->value('id'),
        'governorate_id' => Governorate::query()->latest('id')->value('id'),
        'commercial_reg_no' => '778899',
        'tax_card_no' => '111-222-333',
        ...$overrides,
    ];
}

it('opens the profile form prefilled from the profile', function (): void {
    $this->actingAs($this->buyer)
        ->get(route('buyer.account'))
        ->assertOk()
        ->assertSee(route('buyer.account', ['edit' => 1]), false);

    $this->get(route('buyer.account', ['edit' => 1]))
        ->assertOk()
        ->assertSee(route('buyer.profile.update'), false)
        ->assertSee('value="'.e($this->profile->company_name).'"', false)
        ->assertSee('value="'.e($this->buyer->email).'"', false)
        ->assertSee(__('buyer.contact_locked_hint'));
});

it('saves the organisation details', function (): void {
    $payload = buyerProfilePayload();

    $this->actingAs($this->buyer)
        ->put(route('buyer.profile.update'), $payload)
        ->assertRedirect(route('buyer.account'))
        ->assertSessionHas('status', __('buyer.profile_saved'));

    $this->profile->refresh();

    expect($this->buyer->fresh()->name)->toBe('Nour Hassan')
        ->and($this->profile->only(['job_title', 'company_name', 'company_address', 'business_type_id', 'governorate_id', 'commercial_reg_no', 'tax_card_no']))
        ->toEqual(Arr::except($payload, 'name'));
});

it('never lets the buyer change their sign-in phone or email', function (): void {
    $original = $this->buyer->only(['phone', 'email']);

    $this->actingAs($this->buyer)
        ->put(route('buyer.profile.update'), buyerProfilePayload([
            'phone' => '011 8765 4321',
            'email' => 'new@nilehotels.eg',
        ]))
        ->assertSessionHasNoErrors();

    expect($this->buyer->fresh()->only(['phone', 'email']))->toBe($original);
});

it('rejects empty required fields', function (): void {
    $this->actingAs($this->buyer)
        ->from(route('buyer.account', ['edit' => 1]))
        ->put(route('buyer.profile.update'), buyerProfilePayload([
            'name' => '',
            'company_name' => '',
            'business_type_id' => null,
            'governorate_id' => 999999,
        ]))
        ->assertRedirect(route('buyer.account', ['edit' => 1]))
        ->assertSessionHasErrors(['name', 'company_name', 'business_type_id', 'governorate_id']);

    expect($this->profile->fresh()->company_name)->not->toBe('');
});

it('only updates the signed-in buyer\'s own profile', function (): void {
    $otherBuyer = User::factory()->buyer()->create();
    $otherName = $otherBuyer->buyerProfile->company_name;

    $this->actingAs($this->buyer)
        ->put(route('buyer.profile.update'), buyerProfilePayload(['user_id' => $otherBuyer->id]));

    expect($otherBuyer->buyerProfile->fresh()->company_name)->toBe($otherName)
        ->and($this->profile->fresh()->company_name)->toBe('Nile Hotels Group');
});

it('keeps suppliers and guests out of the buyer profile form', function (): void {
    $this->actingAs(User::factory()->supplier()->create())
        ->put(route('buyer.profile.update'), buyerProfilePayload())
        ->assertRedirect();

    expect($this->profile->fresh()->company_name)->not->toBe('Nile Hotels Group');

    auth()->logout();

    $this->put(route('buyer.profile.update'), buyerProfilePayload())
        ->assertRedirect(route('login'));
});
