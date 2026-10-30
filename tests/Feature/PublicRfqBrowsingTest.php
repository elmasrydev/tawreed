<?php

use App\Models\Category;
use App\Models\Rfq;
use App\Models\User;

beforeEach(function (): void {
    seedReferenceData();

    $this->beverages = Category::where('slug', 'beverages')->firstOrFail();
    $this->coffee = Category::where('slug', 'coffee-hot-drinks')->firstOrFail();
});

it('lets guests search open requests by title, product name or specs', function (): void {
    Rfq::factory()->create(['title' => 'Monthly supply for our cafés', 'product_name' => 'Espresso beans', 'specs' => 'Medium roast.']);
    Rfq::factory()->create(['title' => 'Kitchen restock', 'product_name' => 'Olive oil', 'specs' => 'Arabica coffee, 1kg bags.']);
    Rfq::factory()->create(['title' => 'Site works', 'product_name' => 'Portland cement', 'specs' => '50kg bags.']);

    $this->get(route('rfqs.index', ['q' => 'espresso']))
        ->assertOk()
        ->assertSee('Monthly supply for our cafés')
        ->assertDontSee('Kitchen restock')
        ->assertDontSee('Site works');

    $this->get(route('rfqs.index', ['q' => 'arabica']))
        ->assertOk()
        ->assertSee('Kitchen restock')
        ->assertDontSee('Monthly supply for our cafés');
});

it('lists only open requests and never reveals the buyer, specs or attachments', function (): void {
    $buyer = User::factory()->buyer()->create(['name' => 'Karim Fathy']);
    $buyer->buyerProfile->update(['company_name' => 'Secret Hotel Group']);

    Rfq::factory()->for($buyer, 'buyer')->create(['title' => 'Open request', 'specs' => 'Confidential spec line.']);
    Rfq::factory()->expired()->create(['title' => 'Expired request']);
    Rfq::factory()->draft()->create(['title' => 'Draft request']);
    Rfq::factory()->awarded()->create(['title' => 'Awarded request']);

    $this->get(route('rfqs.index'))
        ->assertOk()
        ->assertSee('Open request')
        ->assertSee($buyer->buyerProfile->businessType->name)
        ->assertSee(route('supplier.rfqs.show', Rfq::where('title', 'Open request')->value('id')), false)
        ->assertDontSee('Expired request')
        ->assertDontSee('Draft request')
        ->assertDontSee('Awarded request')
        ->assertDontSee('Secret Hotel Group')
        ->assertDontSee('Karim Fathy')
        ->assertDontSee('Confidential spec line.');
});

it('filters by a main category or a sub-category slug', function (): void {
    Rfq::factory()->create(['title' => 'Coffee request', 'category_id' => $this->beverages->id, 'subcategory_id' => $this->coffee->id]);
    Rfq::factory()->create(['title' => 'Juice request', 'category_id' => $this->beverages->id]);
    Rfq::factory()->create(['title' => 'Cement request', 'category_id' => Category::where('slug', 'cement')->value('id')]);

    $this->get(route('rfqs.index', ['category' => 'beverages']))
        ->assertOk()
        ->assertSee('Coffee request')
        ->assertSee('Juice request')
        ->assertDontSee('Cement request');

    $this->get(route('rfqs.index', ['category' => 'coffee-hot-drinks']))
        ->assertOk()
        ->assertSee('Coffee request')
        ->assertDontSee('Juice request');
});

it('shows an empty state when nothing matches', function (): void {
    Rfq::factory()->create(['title' => 'Coffee request']);

    $this->get(route('rfqs.index', ['q' => 'no-such-product']))
        ->assertOk()
        ->assertSee(__('landing.rfqs_empty_title'))
        ->assertSee(route('rfqs.index'), false);
});

it('lists every active category with its sub-categories linking to their requests', function (): void {
    Category::where('slug', 'textiles')->update(['is_active' => false]);
    Rfq::factory()->count(2)->create(['category_id' => $this->beverages->id]);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSee($this->beverages->name)
        ->assertSee(route('rfqs.index', ['category' => 'beverages']), false)
        ->assertSee(route('rfqs.index', ['category' => 'coffee-hot-drinks']), false)
        ->assertSee(trans_choice('landing.category_open_rfqs', 2))
        ->assertDontSee(Category::where('slug', 'textiles')->value('name_ar'));

    expect(substr_count($this->get(route('categories.index'))->getContent(), 'data-testid="category-card"'))
        ->toBe(Category::query()->main()->count());
});
