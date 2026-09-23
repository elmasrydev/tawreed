<?php

use App\Enums\RfqStatus;
use App\Enums\SupplyType;
use App\Events\RfqPublished;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Rfq;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();

    $this->buyer = User::factory()->buyer()->create();
    $this->beverages = Category::where('slug', 'beverages')->firstOrFail();
    $this->coffee = Category::where('slug', 'coffee-hot-drinks')->firstOrFail();
    $this->kilogram = Unit::where('slug', 'kilogram')->firstOrFail();
    $this->cairo = Governorate::where('slug', 'cairo')->firstOrFail();
});

/**
 * Drives the wizard through the three input steps and posts from the review.
 */
function completeWizard(array $overrides = []): Testable
{
    $data = array_merge([
        'title' => 'Roasted espresso beans',
        'category_id' => test()->beverages->id,
        'subcategory_id' => test()->coffee->id,
        'specs' => '80% Arabica / 20% Robusta blend, medium-dark roast, 1kg valve bags.',
        'quantity' => '500',
        'unit_id' => test()->kilogram->id,
        'governorate_id' => test()->cairo->id,
        'delivery_date' => now()->addWeeks(3)->toDateString(),
        'supply_type' => SupplyType::OneTime->value,
        'quote_deadline' => now()->addWeek()->toDateString(),
        'notes' => 'A sample is required before contracting.',
    ], $overrides);

    return Livewire::actingAs(test()->buyer)
        ->test('buyer.create-rfq')
        ->set($data)
        ->call('next')
        ->call('next')
        ->call('next')
        ->assertSet('step', 4)
        ->call('next');
}

it('publishes a request from the review step and lands on my requests', function (): void {
    Event::fake([RfqPublished::class]);

    completeWizard()->assertRedirect(route('buyer.rfqs.index'));

    $rfq = Rfq::sole();

    expect($rfq->status)->toBe(RfqStatus::Open)
        ->and($rfq->buyer_id)->toBe($this->buyer->id)
        ->and($rfq->title)->toBe('Roasted espresso beans')
        ->and((float) $rfq->quantity)->toBe(500.0)
        ->and($rfq->reference)->toStartWith('RFQ-'.now()->year.'-')
        ->and($rfq->published_at)->not->toBeNull();

    Event::assertDispatched(RfqPublished::class);
});

it('numbers references sequentially within the year', function (): void {
    completeWizard();
    completeWizard(['title' => 'Paper cups 12oz']);

    expect(Rfq::orderBy('id')->pluck('reference')->all())
        ->toBe(['RFQ-'.now()->year.'-00001', 'RFQ-'.now()->year.'-00002']);
});

it('holds the buyer on step one until the product details are valid', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq')
        ->set('title', '')
        ->set('specs', 'too short')
        ->call('next')
        ->assertHasErrors(['title', 'category_id', 'specs'])
        ->assertSet('step', 1);

    expect(Rfq::count())->toBe(0);
});

it('rejects a sub-category that does not belong to the chosen category', function (): void {
    $unrelated = Category::where('slug', 'cement')->firstOrFail();

    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq')
        ->set('title', 'Roasted espresso beans')
        ->set('category_id', $this->beverages->id)
        ->set('subcategory_id', $unrelated->id)
        ->set('specs', 'A specification long enough to pass validation.')
        ->call('next')
        ->assertHasErrors('subcategory_id');
});

it('clears the sub-category when the main category changes', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq')
        ->set('category_id', $this->beverages->id)
        ->set('subcategory_id', $this->coffee->id)
        ->set('category_id', Category::where('slug', 'packaging')->value('id'))
        ->assertSet('subcategory_id', null);
});

it('refuses a quote deadline that falls after the delivery date', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq')
        ->set([
            'title' => 'Roasted espresso beans',
            'category_id' => $this->beverages->id,
            'specs' => 'A specification long enough to pass validation.',
            'quantity' => '500',
            'unit_id' => $this->kilogram->id,
            'governorate_id' => $this->cairo->id,
            'delivery_date' => now()->addWeek()->toDateString(),
            'quote_deadline' => now()->addWeeks(3)->toDateString(),
        ])
        ->call('next')
        ->call('next')
        ->call('next')
        ->assertHasErrors('quote_deadline')
        ->assertSet('step', 3);
});

it('refuses a delivery date in the past', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq')
        ->set([
            'title' => 'Roasted espresso beans',
            'category_id' => $this->beverages->id,
            'specs' => 'A specification long enough to pass validation.',
            'quantity' => '500',
            'unit_id' => $this->kilogram->id,
            'governorate_id' => $this->cairo->id,
            'delivery_date' => now()->subDay()->toDateString(),
        ])
        ->call('next')
        ->call('next')
        ->assertHasErrors('delivery_date')
        ->assertSet('step', 2);
});

it('keeps the recurrence note only for a recurring supply', function (): void {
    completeWizard([
        'supply_type' => SupplyType::OneTime->value,
        'recurrence_note' => 'every two weeks',
    ]);

    expect(Rfq::sole()->recurrence_note)->toBeNull();
});

it('stores attachments with the request', function (): void {
    Storage::fake('public');

    completeWizard(['attachments' => [UploadedFile::fake()->image('beans.jpg')]]);

    expect(Rfq::sole()->getMedia('attachments'))->toHaveCount(1);
});

it('keeps suppliers out of the buyer wizard', function (): void {
    Livewire::actingAs(User::factory()->supplier()->create())
        ->test('buyer.create-rfq')
        ->assertForbidden();
});

it('requires a verified phone before posting a request', function (): void {
    $unverified = User::factory()->buyer()->unverified()->create();

    Livewire::actingAs($unverified)
        ->test('buyer.create-rfq')
        ->assertForbidden();
});

it('lists drafts under their own tab with a way back into the wizard', function (): void {
    Rfq::factory()->for($this->buyer, 'buyer')->create(['title' => 'Published request']);
    $draft = Rfq::factory()->for($this->buyer, 'buyer')->draft()->create(['title' => 'Draft request']);

    $this->actingAs($this->buyer)
        ->get(route('buyer.rfqs.index', ['status' => 'draft']))
        ->assertOk()
        ->assertSee('Draft request')
        ->assertDontSee('Published request')
        ->assertSee(route('buyer.rfqs.edit', $draft));
});

it('saves a draft with only a title and lands on the drafts tab', function (): void {
    Event::fake([RfqPublished::class]);

    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq')
        ->set('title', 'Half-finished request')
        ->call('saveDraft')
        ->assertHasNoErrors()
        ->assertRedirect(route('buyer.rfqs.index', ['status' => 'draft']));

    $rfq = Rfq::sole();

    expect($rfq->status)->toBe(RfqStatus::Draft)
        ->and($rfq->category_id)->toBeNull()
        ->and($rfq->quote_deadline)->toBeNull()
        ->and($rfq->published_at)->toBeNull();

    Event::assertNotDispatched(RfqPublished::class);
});

it('requires a title and valid values to save a draft', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq')
        ->set('quantity', '-4')
        ->call('saveDraft')
        ->assertHasErrors(['title', 'quantity']);

    expect(Rfq::count())->toBe(0);
});

it('stores attachments with a draft', function (): void {
    Storage::fake('public');

    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq')
        ->set('title', 'Request with a spec sheet')
        ->set('attachments', [UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf')])
        ->call('saveDraft');

    expect(Rfq::sole()->getMedia('attachments'))->toHaveCount(1);
});

it('resumes a draft and publishes the same row', function (): void {
    Event::fake([RfqPublished::class]);

    $draft = Rfq::factory()->for($this->buyer, 'buyer')->draft()->create([
        'title' => 'Resumed request',
        'category_id' => null,
        'quote_deadline' => null,
    ]);

    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq', ['rfq' => $draft])
        ->assertSet('title', 'Resumed request')
        ->assertSet('draftId', $draft->id)
        ->set([
            'category_id' => $this->beverages->id,
            'specs' => 'A specification long enough to pass validation.',
            'quantity' => '500',
            'unit_id' => $this->kilogram->id,
            'governorate_id' => $this->cairo->id,
            'delivery_date' => now()->addWeeks(3)->toDateString(),
            'quote_deadline' => now()->addWeek()->toDateString(),
        ])
        ->call('next')->call('next')->call('next')->call('next')
        ->assertRedirect(route('buyer.rfqs.index'));

    expect(Rfq::count())->toBe(1)
        ->and($draft->fresh()->status)->toBe(RfqStatus::Open)
        ->and($draft->fresh()->published_at)->not->toBeNull();

    Event::assertDispatched(RfqPublished::class);
});

it('saving a resumed draft again updates the same row', function (): void {
    $draft = Rfq::factory()->for($this->buyer, 'buyer')->draft()->create(['title' => 'Old title']);

    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq', ['rfq' => $draft])
        ->set('title', 'New title')
        ->call('saveDraft');

    expect(Rfq::count())->toBe(1)
        ->and($draft->fresh()->title)->toBe('New title')
        ->and($draft->fresh()->status)->toBe(RfqStatus::Draft);
});

it('sends the buyer back to the first incomplete step when publishing a draft', function (): void {
    $draft = Rfq::factory()->for($this->buyer, 'buyer')->draft()->create(['specs' => null]);

    Livewire::actingAs($this->buyer)
        ->test('buyer.create-rfq', ['rfq' => $draft])
        ->set('step', 4)
        ->call('next')
        ->assertHasErrors('specs')
        ->assertSet('step', 1);

    expect($draft->fresh()->status)->toBe(RfqStatus::Draft);
});

it('refuses to resume another buyer draft or a published request', function (): void {
    $othersDraft = Rfq::factory()->draft()->create();
    $published = Rfq::factory()->for($this->buyer, 'buyer')->create();

    Livewire::actingAs($this->buyer)->test('buyer.create-rfq', ['rfq' => $othersDraft])->assertForbidden();
    Livewire::actingAs($this->buyer)->test('buyer.create-rfq', ['rfq' => $published])->assertForbidden();
});

it('renders an incomplete draft on the detail page and lets the buyer delete it', function (): void {
    $draft = Rfq::factory()->for($this->buyer, 'buyer')->draft()->create([
        'title' => 'Bare draft',
        'category_id' => null,
        'unit_id' => null,
        'governorate_id' => null,
        'specs' => null,
        'quantity' => null,
        'delivery_date' => null,
        'quote_deadline' => null,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('buyer.rfqs.show', $draft))
        ->assertOk()
        ->assertSee('Bare draft');

    $this->actingAs($this->buyer)
        ->get(route('buyer.rfqs.index'))
        ->assertOk()
        ->assertSee('Bare draft');

    $this->actingAs($this->buyer)
        ->delete(route('buyer.rfqs.destroy', $draft))
        ->assertRedirect(route('buyer.rfqs.index', ['status' => 'draft']));

    expect(Rfq::count())->toBe(0);
});

it('never deletes a published request from the buyer area', function (): void {
    $published = Rfq::factory()->for($this->buyer, 'buyer')->create();

    $this->actingAs($this->buyer)
        ->delete(route('buyer.rfqs.destroy', $published))
        ->assertForbidden();

    expect($published->fresh())->not->toBeNull();
});

it('stops a buyer opening another buyer request', function (): void {
    $other = Rfq::factory()->create();

    $this->actingAs($this->buyer)
        ->get(route('buyer.rfqs.show', $other))
        ->assertForbidden();
});
