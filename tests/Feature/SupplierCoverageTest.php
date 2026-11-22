<?php

use App\Models\Category;
use App\Models\Governorate;
use App\Models\User;

beforeEach(function (): void {
    seedReferenceData();

    $this->supplier = User::factory()->supplier()->create();
    $this->profile = $this->supplier->supplierProfile;
});

it('opens the coverage form prefilled from the profile', function (): void {
    $governorate = Governorate::query()->first();
    $this->profile->governorates()->sync([$governorate->id]);
    $this->profile->update(['payment_method' => 'Bank transfer']);

    $this->actingAs($this->supplier)
        ->get(route('supplier.account', ['tab' => 'coverage']))
        ->assertOk()
        ->assertSee(e(route('supplier.account', ['tab' => 'coverage', 'edit' => 1])), false);

    $this->get(route('supplier.account', ['tab' => 'coverage', 'edit' => 1]))
        ->assertOk()
        ->assertSee(route('supplier.coverage.update'), false)
        ->assertSee('value="Bank transfer"', false)
        ->assertSee($governorate->name);
});

it('saves the coverage areas, categories and payment method', function (): void {
    $governorateIds = Governorate::query()->take(2)->pluck('id')->all();
    $categoryIds = Category::query()->main()->take(2)->pluck('id')->all();

    $this->actingAs($this->supplier)
        ->put(route('supplier.coverage.update'), [
            'governorate_ids' => $governorateIds,
            'category_ids' => $categoryIds,
            'payment_method' => 'Cash on delivery',
        ])
        ->assertRedirect(route('supplier.account', ['tab' => 'coverage']))
        ->assertSessionHas('status', __('supplier.coverage_saved'));

    $this->profile->refresh();

    expect($this->profile->governorates->pluck('id')->sort()->values()->all())->toBe(collect($governorateIds)->sort()->values()->all())
        ->and($this->profile->categories->pluck('id')->sort()->values()->all())->toBe(collect($categoryIds)->sort()->values()->all())
        ->and($this->profile->payment_method)->toBe('Cash on delivery');
});

it('clears the categories and payment method when none are sent', function (): void {
    $this->profile->categories()->sync(Category::query()->main()->take(1)->pluck('id'));
    $this->profile->update(['payment_method' => 'CIB']);

    $this->actingAs($this->supplier)
        ->put(route('supplier.coverage.update'), [
            'governorate_ids' => [Governorate::query()->value('id')],
        ])
        ->assertSessionHasNoErrors();

    $this->profile->refresh();

    expect($this->profile->categories)->toBeEmpty()
        ->and($this->profile->payment_method)->toBeNull();
});

it('keeps an admin-assigned sub-category selectable', function (): void {
    $subcategory = Category::query()->whereNotNull('parent_id')->first()
        ?? Category::factory()->create(['parent_id' => Category::query()->main()->value('id')]);
    $this->profile->categories()->sync([$subcategory->id]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.account', ['tab' => 'coverage', 'edit' => 1]))
        ->assertOk()
        ->assertSee('value="'.$subcategory->id.'"', false);
});

it('requires at least one coverage area', function (): void {
    $original = Governorate::query()->take(1)->pluck('id')->all();
    $this->profile->governorates()->sync($original);

    $this->actingAs($this->supplier)
        ->from(route('supplier.account', ['tab' => 'coverage', 'edit' => 1]))
        ->put(route('supplier.coverage.update'), [
            'governorate_ids' => [],
            'category_ids' => [999999],
        ])
        ->assertRedirect(route('supplier.account', ['tab' => 'coverage', 'edit' => 1]))
        ->assertSessionHasErrors(['governorate_ids', 'category_ids.0']);

    expect($this->profile->governorates()->pluck('governorates.id')->all())->toBe($original);
});

it('keeps buyers and guests out of the supplier coverage form', function (): void {
    $payload = ['governorate_ids' => [Governorate::query()->value('id')]];

    $this->actingAs(User::factory()->buyer()->create())
        ->put(route('supplier.coverage.update'), $payload)
        ->assertRedirect(route('buyer.home'));

    auth()->logout();

    $this->put(route('supplier.coverage.update'), $payload)
        ->assertRedirect(route('login'));
});
