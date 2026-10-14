<?php

use App\Models\Conversation;
use App\Models\Rfq;
use App\Models\User;

beforeEach(function (): void {
    seedReferenceData();

    $this->buyer = User::factory()->buyer()->create();
    $this->supplier = User::factory()->supplier()->create();
});

it('lists the buyer\'s awarded requests with the supplier and total', function (): void {
    $order = awardedDeal($this->buyer, $this->supplier, ['title' => 'Olive oil, 200 litres']);

    $this->actingAs($this->buyer)
        ->get(route('buyer.orders.index'))
        ->assertOk()
        ->assertSee('Olive oil, 200 litres')
        ->assertSee($order->reference)
        ->assertSee($this->supplier->supplierProfile->company_name)
        ->assertSee(number_format($order->awardedQuote->grandTotal(), 2))
        ->assertSee(route('buyer.rfqs.show', $order), false);
});

it('leaves out open requests and other buyers\' orders', function (): void {
    Rfq::factory()->create(['buyer_id' => $this->buyer->id, 'title' => 'Still collecting quotes']);
    awardedDeal(User::factory()->buyer()->create(), $this->supplier, ['title' => 'Another buyer\'s order']);

    $this->actingAs($this->buyer)
        ->get(route('buyer.orders.index'))
        ->assertOk()
        ->assertSee(__('buyer.no_orders'))
        ->assertDontSee('Still collecting quotes')
        ->assertDontSee('Another buyer\'s order');
});

it('links each order to its conversation', function (): void {
    $order = awardedDeal($this->buyer, $this->supplier);
    $conversation = Conversation::factory()->create();
    $conversation->update([
        'rfq_id' => $order->id,
        'quote_id' => $order->awarded_quote_id,
        'buyer_id' => $this->buyer->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('buyer.orders.index'))
        ->assertSee(route('buyer.chats.show', $conversation), false);
});

it('shows order history in the buyer navigation', function (): void {
    $this->actingAs($this->buyer)
        ->get(route('buyer.home'))
        ->assertSee(route('buyer.orders.index'), false);
});

it('keeps suppliers out of the buyer order history', function (): void {
    $this->actingAs($this->supplier)
        ->get(route('buyer.orders.index'))
        ->assertRedirect();
});
