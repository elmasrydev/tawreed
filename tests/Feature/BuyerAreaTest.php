<?php

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\RejectionReason;
use App\Models\Rfq;
use App\Models\User;

beforeEach(function (): void {
    seedReferenceData();

    $this->buyer = User::factory()->buyer()->create();
});

it('shows the dashboard figures from the buyer own data', function (): void {
    $closing = Rfq::factory()->for($this->buyer, 'buyer')->endingSoon()->create(['title' => 'Closing tomorrow']);
    Rfq::factory()->for($this->buyer, 'buyer')->create(['title' => 'Waiting for quotes', 'quote_deadline' => now()->addDays(6)]);
    Rfq::factory()->for($this->buyer, 'buyer')->awarded()->create();
    Quote::factory()->for($closing)->create(['total_price' => 45000]);
    Rfq::factory()->create(['title' => 'Someone else request']);

    $this->actingAs($this->buyer)
        ->get(route('buyer.home'))
        ->assertOk()
        ->assertViewHas('kpis', fn (array $kpis): bool => $kpis['open'] === 2
            && $kpis['closingSoon'] === 1
            && $kpis['pending'] === 1
            && $kpis['pendingSinceYesterday'] === 1
            && $kpis['awardedThisMonth'] === 1)
        ->assertViewHas('attentionCount', 2)
        ->assertSee('Closing tomorrow')
        ->assertSee('Waiting for quotes')
        ->assertSee('45,000')
        ->assertDontSee('Someone else request');
});

it('keeps drafts out of the dashboard', function (): void {
    Rfq::factory()->for($this->buyer, 'buyer')->draft()->create(['title' => 'Unfinished draft', 'quote_deadline' => null]);

    $this->actingAs($this->buyer)
        ->get(route('buyer.home'))
        ->assertOk()
        ->assertDontSee('Unfinished draft');
});

it('filters my requests by status and hides empty tabs', function (): void {
    Rfq::factory()->for($this->buyer, 'buyer')->create(['title' => 'Open request']);
    Rfq::factory()->for($this->buyer, 'buyer')->awarded()->create(['title' => 'Awarded request']);

    $response = $this->actingAs($this->buyer)
        ->get(route('buyer.rfqs.index', ['status' => 'awarded']))
        ->assertOk()
        ->assertSee('Awarded request')
        ->assertDontSee('Open request');

    $keys = collect($response->viewData('tabs'))->pluck('key')->all();

    expect($keys)->toBe(['all', 'open', 'awarded']);
});

it('lists every received quote in the inbox and filters by status', function (): void {
    $rfq = Rfq::factory()->for($this->buyer, 'buyer')->create();
    $pending = Quote::factory()->for($rfq)->create();
    $rejected = Quote::factory()->for($rfq)->create([
        'status' => QuoteStatus::Rejected,
        'rejection_reason_id' => RejectionReason::first()->id,
    ]);
    Quote::factory()->create();

    $this->actingAs($this->buyer)
        ->get(route('buyer.quotes.index'))
        ->assertOk()
        ->assertViewHas('quotes', fn ($quotes): bool => $quotes->total() === 2);

    $this->actingAs($this->buyer)
        ->get(route('buyer.quotes.index', ['status' => 'rejected']))
        ->assertOk()
        ->assertViewHas('quotes', fn ($quotes): bool => $quotes->pluck('id')->all() === [$rejected->id])
        ->assertSee($rejected->supplier->supplierProfile->company_name)
        ->assertDontSee($pending->supplier->supplierProfile->company_name);
});

it('shows the buyer settings read-only with what suppliers see', function (): void {
    $profile = $this->buyer->buyerProfile;

    $this->actingAs($this->buyer)
        ->get(route('buyer.account'))
        ->assertOk()
        ->assertSee($profile->company_name)
        ->assertSee($profile->anonymousLabel())
        ->assertSee(route('logout'));
});

it('redirects the quotes page of a draft back to the wizard', function (): void {
    $draft = Rfq::factory()->for($this->buyer, 'buyer')->draft()->create();

    Livewire\Livewire::actingAs($this->buyer)
        ->test('buyer.rfq-quotes', ['rfq' => $draft])
        ->assertRedirect(route('buyer.rfqs.edit', $draft));
});

it('opens the award confirmation before selecting a quote', function (): void {
    $rfq = Rfq::factory()->for($this->buyer, 'buyer')->create();
    $quote = Quote::factory()->for($rfq)->create();

    Livewire\Livewire::actingAs($this->buyer)
        ->test('buyer.rfq-quotes', ['rfq' => $rfq])
        ->call('startSelect', $quote->id)
        ->assertSet('showAwardModal', true)
        ->assertSee($this->buyer->buyerProfile->company_name)
        ->call('cancelSelect')
        ->assertSet('selectingQuoteId', null);

    expect($quote->fresh()->status)->toBe(QuoteStatus::Pending);
});
