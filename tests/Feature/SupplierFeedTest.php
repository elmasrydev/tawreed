<?php

use App\Enums\UserRole;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\SupplierProfile;
use App\Models\User;
use App\ViewModels\AnonymousRfq;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    seedReferenceData();

    $this->supplier = User::factory()->supplier()->create();
});

it('shows a request in the feed without any trace of the buyer', function (): void {
    $buyer = User::factory()->buyer()->create(['name' => 'Ahmed Samy']);
    $buyer->buyerProfile->update(['company_name' => 'Cafe City', 'company_address' => 'New Cairo']);

    Rfq::factory()->for($buyer, 'buyer')->create(['title' => 'Roasted espresso beans']);

    $response = $this->actingAs($this->supplier)->get(route('supplier.feed'));

    $response->assertOk()
        ->assertSee('Roasted espresso beans')
        ->assertDontSee('Cafe City')
        ->assertDontSee('New Cairo')
        ->assertDontSee('Ahmed Samy')
        ->assertDontSee($buyer->email)
        ->assertDontSee($buyer->phone);
});

it('describes the buyer only by entity type and governorate', function (): void {
    $buyer = User::factory()->buyer()->create();
    $buyer->buyerProfile->update([
        'business_type_id' => BusinessType::where('slug', 'restaurant-cafe')->value('id'),
        'company_name' => 'Cafe City',
    ]);

    // The label pairs the buyer's entity type with the request's delivery
    // governorate, which is the location a supplier actually needs.
    $rfq = Rfq::factory()->for($buyer, 'buyer')->create([
        'governorate_id' => Governorate::where('slug', 'cairo')->value('id'),
    ]);

    app()->setLocale('en');
    expect(AnonymousRfq::make($rfq->fresh(), $this->supplier)->buyerLabel())
        ->toBe('Restaurant / Café — Cairo');

    app()->setLocale('ar');
    expect(AnonymousRfq::make($rfq->fresh(), $this->supplier)->buyerLabel())
        ->toBe('مطعم / كافيه — القاهرة')
        ->not->toContain('Cafe City');
});

it('never exposes a competitor price or identity on the request page', function (): void {
    $rfq = Rfq::factory()->create();
    $rival = User::factory()->supplier()->create();
    $rival->supplierProfile->update(['company_name' => 'Nile Mills Co.']);

    Quote::factory()->for($rfq)->create([
        'supplier_id' => $rival->id,
        'total_price' => 89000,
        'unit_price' => 178,
    ]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.rfqs.show', $rfq))
        ->assertOk()
        ->assertDontSee('Nile Mills Co.')
        ->assertDontSee('89,000')
        ->assertDontSee('178.00');
});

it('hides draft and expired requests from the feed', function (): void {
    Rfq::factory()->create(['title' => 'Open request']);
    Rfq::factory()->draft()->create(['title' => 'Draft request']);
    Rfq::factory()->expired()->create(['title' => 'Expired request']);

    $this->actingAs($this->supplier)
        ->get(route('supplier.feed'))
        ->assertSee('Open request')
        ->assertDontSee('Draft request')
        ->assertDontSee('Expired request');
});

it('filters the feed by category and governorate', function (): void {
    $beverages = Category::where('slug', 'beverages')->firstOrFail();
    $cement = Category::where('slug', 'building-materials')->firstOrFail();
    $cairo = Governorate::where('slug', 'cairo')->firstOrFail();
    $giza = Governorate::where('slug', 'giza')->firstOrFail();

    Rfq::factory()->create(['title' => 'Espresso beans', 'category_id' => $beverages->id, 'governorate_id' => $cairo->id]);
    Rfq::factory()->create(['title' => 'Portland cement', 'category_id' => $cement->id, 'governorate_id' => $giza->id]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.feed', ['category' => $beverages->id]))
        ->assertSee('Espresso beans')
        ->assertDontSee('Portland cement');

    $this->actingAs($this->supplier)
        ->get(route('supplier.feed', ['governorate' => $giza->id]))
        ->assertSee('Portland cement')
        ->assertDontSee('Espresso beans');
});

it('lets only a verified supplier quote an open request', function (): void {
    $rfq = Rfq::factory()->create();

    $pending = User::factory()
        ->has(SupplierProfile::factory()->pending(), 'supplierProfile')
        ->create(['role' => UserRole::Supplier]);

    expect(Gate::forUser($this->supplier)->allows('quote', $rfq))->toBeTrue()
        ->and(Gate::forUser($pending)->allows('quote', $rfq))->toBeFalse();
});

it('refuses a second quote on the same request', function (): void {
    $rfq = Rfq::factory()->create();
    Quote::factory()->for($rfq)->create(['supplier_id' => $this->supplier->id]);

    expect(Gate::forUser($this->supplier)->allows('quote', $rfq->fresh()))->toBeFalse();
});

it('refuses a quote once the deadline has passed', function (): void {
    $closed = Rfq::factory()->create(['quote_deadline' => now()->subDay()]);

    expect(Gate::forUser($this->supplier)->allows('quote', $closed))->toBeFalse();
});

it('marks a request the supplier has already quoted', function (): void {
    $rfq = Rfq::factory()->create();
    Quote::factory()->for($rfq)->create(['supplier_id' => $this->supplier->id]);

    $view = AnonymousRfq::make($rfq->fresh()->load('quotes'), $this->supplier);

    expect($view->hasOwnQuote())->toBeTrue()
        ->and(AnonymousRfq::make($rfq->fresh()->load('quotes'), User::factory()->supplier()->create())->hasOwnQuote())
        ->toBeFalse();
});

it('keeps buyers out of the supplier feed', function (): void {
    $this->actingAs(User::factory()->buyer()->create())
        ->get(route('supplier.feed'))
        ->assertRedirect(route('buyer.home'));
});
