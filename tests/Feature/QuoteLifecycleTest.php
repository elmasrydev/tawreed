<?php

use App\Actions\Quote\SelectQuote;
use App\Actions\Quote\SubmitQuote;
use App\Enums\MessageType;
use App\Enums\QuoteStatus;
use App\Enums\ReportType;
use App\Enums\RfqStatus;
use App\Enums\UserRole;
use App\Enums\VatMode;
use App\Models\Conversation;
use App\Models\Quote;
use App\Models\RejectionReason;
use App\Models\Report;
use App\Models\Rfq;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();

    $this->buyer = User::factory()->buyer()->create();
    $this->supplier = User::factory()->supplier()->create();
    $this->rfq = Rfq::factory()->for($this->buyer, 'buyer')->create();
});

/**
 * Valid input for the supplier quote form.
 *
 * @return array<string, mixed>
 */
function quoteFormData(array $overrides = []): array
{
    return array_merge([
        'unit_price' => '185',
        'total_price' => '92500',
        'min_order_qty' => '100',
        'vat_mode' => VatMode::Included->value,
        'delivery_cost' => '0',
        'expected_delivery_date' => now()->addWeeks(2)->toDateString(),
        'validity_days' => '10',
        'payment_terms' => '50% upfront',
        'sample_availability' => 'Free sample',
        'brand_origin' => 'Brazil / Vietnam',
        'extra_specs' => '1kg valve-bag packing',
        'warranty_policy' => 'Immediate replacement of non-conforming bags.',
    ], $overrides);
}

it('submits a quote and refreshes the request totals', function (): void {
    Livewire::actingAs($this->supplier)
        ->test('supplier.submit-quote', ['rfq' => $this->rfq])
        ->set(quoteFormData())
        ->call('submit')
        ->assertRedirect(route('supplier.quotes.index'));

    $quote = Quote::sole();
    $this->rfq->refresh();

    expect($quote->status)->toBe(QuoteStatus::Pending)
        ->and($quote->supplier_id)->toBe($this->supplier->id)
        ->and((float) $quote->total_price)->toBe(92500.0)
        ->and($this->rfq->quotes_count)->toBe(1)
        ->and((float) $this->rfq->best_price)->toBe(92500.0);
});

it('requires a vat amount when vat is excluded', function (): void {
    Livewire::actingAs($this->supplier)
        ->test('supplier.submit-quote', ['rfq' => $this->rfq])
        ->set(quoteFormData(['vat_mode' => VatMode::Excluded->value, 'vat_amount' => '']))
        ->call('submit')
        ->assertHasErrors('vat_amount');

    expect(Quote::count())->toBe(0);
});

it('adds vat and delivery into the grand total shown to the supplier', function (): void {
    Livewire::actingAs($this->supplier)
        ->test('supplier.submit-quote', ['rfq' => $this->rfq])
        ->set(quoteFormData([
            'vat_mode' => VatMode::Excluded->value,
            'vat_amount' => '12950',
            'delivery_cost' => '1500',
        ]))
        ->assertSet('total_price', '92500')
        ->assertSee('106,950.00');
});

it('blocks a quote that hides contact details in the free text', function (): void {
    Livewire::actingAs($this->supplier)
        ->test('supplier.submit-quote', ['rfq' => $this->rfq])
        ->set(quoteFormData(['extra_specs' => 'Call us on 01023456789 for a better price']))
        ->call('submit')
        ->assertHasErrors('extra_specs');

    expect(Quote::count())->toBe(0);
});

it('flags a quote for moderation when contact details reach the database', function (): void {
    app(SubmitQuote::class)->handle(
        $this->rfq,
        $this->supplier,
        [...quoteFormData(), 'warranty_policy' => 'WhatsApp us on 01198765432'],
    );

    $report = Report::sole();

    expect($report->type)->toBe(ReportType::ContactDetailsBypass)
        ->and($report->reportable_type)->toBe(Quote::class)
        ->and($report->reason)->toContain('warranty_policy');
});

it('stops an unverified supplier reaching the quote form', function (): void {
    $pending = User::factory()
        ->has(SupplierProfile::factory()->pending(), 'supplierProfile')
        ->create(['role' => UserRole::Supplier]);

    Livewire::actingAs($pending)
        ->test('supplier.submit-quote', ['rfq' => $this->rfq])
        ->assertForbidden();
});

it('shortlists a quote and toggles it back', function (): void {
    $quote = Quote::factory()->for($this->rfq)->create(['supplier_id' => $this->supplier->id]);

    $component = Livewire::actingAs($this->buyer)
        ->test('buyer.rfq-quotes', ['rfq' => $this->rfq])
        ->call('shortlist', $quote->id);

    expect($quote->fresh()->status)->toBe(QuoteStatus::Shortlisted);

    $component->call('shortlist', $quote->id);

    expect($quote->fresh()->status)->toBe(QuoteStatus::Pending);
});

it('rejects a quote with a reason', function (): void {
    $quote = Quote::factory()->for($this->rfq)->create(['supplier_id' => $this->supplier->id]);
    $reason = RejectionReason::where('slug', 'price-too-high')->firstOrFail();

    Livewire::actingAs($this->buyer)
        ->test('buyer.rfq-quotes', ['rfq' => $this->rfq])
        ->call('startReject', $quote->id)
        ->assertSet('rejectingQuoteId', $quote->id)
        ->call('reject', $reason->id)
        ->assertSet('rejectingQuoteId', null);

    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatus::Rejected)
        ->and($quote->rejection_reason_id)->toBe($reason->id)
        ->and($quote->rejected_at)->not->toBeNull();
});

it('awards the request, closes the other quotes and opens the chat on selection', function (): void {
    $winner = Quote::factory()->for($this->rfq)->create([
        'supplier_id' => $this->supplier->id,
        'total_price' => 89000,
    ]);
    $loser = Quote::factory()->for($this->rfq)->create(['total_price' => 96000]);

    Livewire::actingAs($this->buyer)
        ->test('buyer.rfq-quotes', ['rfq' => $this->rfq])
        ->call('select', $winner->id)
        ->assertRedirect(route('buyer.rfqs.show', $this->rfq));

    $this->rfq->refresh();

    expect($winner->fresh()->status)->toBe(QuoteStatus::Selected)
        ->and($winner->fresh()->selected_at)->not->toBeNull()
        ->and($loser->fresh()->status)->toBe(QuoteStatus::Rejected)
        ->and($this->rfq->status)->toBe(RfqStatus::Awarded)
        ->and($this->rfq->awarded_quote_id)->toBe($winner->id);

    $conversation = Conversation::sole();

    expect($conversation->buyer_id)->toBe($this->buyer->id)
        ->and($conversation->supplier_id)->toBe($this->supplier->id)
        ->and($conversation->quote_id)->toBe($winner->id)
        ->and($conversation->messages()->first()->type)->toBe(MessageType::System);
});

it('counts the awarded deal towards the supplier record', function (): void {
    $quote = Quote::factory()->for($this->rfq)->create(['supplier_id' => $this->supplier->id]);

    expect($this->supplier->supplierProfile->completed_deals_count)->toBe(0);

    app(SelectQuote::class)->handle($quote);

    expect($this->supplier->supplierProfile->fresh()->completed_deals_count)->toBe(1);
});

it('stops a different buyer deciding on the quotes', function (): void {
    Quote::factory()->for($this->rfq)->create(['supplier_id' => $this->supplier->id]);

    Livewire::actingAs(User::factory()->buyer()->create())
        ->test('buyer.rfq-quotes', ['rfq' => $this->rfq])
        ->assertForbidden();
});

it('stops the supplier deciding on their own quote', function (): void {
    $quote = Quote::factory()->for($this->rfq)->create(['supplier_id' => $this->supplier->id]);

    expect(Gate::forUser($this->supplier)->allows('decide', $quote))->toBeFalse()
        ->and(Gate::forUser($this->buyer)->allows('decide', $quote))->toBeTrue();
});

it('refuses a second decision once the request is awarded', function (): void {
    $first = Quote::factory()->for($this->rfq)->create(['supplier_id' => $this->supplier->id]);
    $second = Quote::factory()->for($this->rfq)->create();

    app(SelectQuote::class)->handle($first);

    expect(Gate::forUser($this->buyer)->allows('decide', $second->fresh()))->toBeFalse();
});

it('shows the buyer every supplier identity on the comparison screen', function (): void {
    $this->supplier->supplierProfile->update(['company_name' => 'Delta Coffee Roasters']);
    Quote::factory()->for($this->rfq)->create([
        'supplier_id' => $this->supplier->id,
        'total_price' => 92500,
    ]);

    Livewire::actingAs($this->buyer)
        ->test('buyer.rfq-quotes', ['rfq' => $this->rfq])
        ->assertSee('Delta Coffee Roasters')
        ->assertSee('92,500.00');
});

it('lists received quotes in the inbox with their request', function (): void {
    Quote::factory()->for($this->rfq)->create(['supplier_id' => $this->supplier->id]);
    Rfq::factory()->for($this->buyer, 'buyer')->create(['title' => 'Request without quotes']);

    $this->actingAs($this->buyer)
        ->get(route('buyer.quotes.index'))
        ->assertOk()
        ->assertSee($this->rfq->title)
        ->assertDontSee('Request without quotes');
});
