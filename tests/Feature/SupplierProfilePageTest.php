<?php

use App\Models\Conversation;
use App\Models\Review;
use App\Models\User;

beforeEach(function (): void {
    seedReferenceData();

    $this->buyer = User::factory()->buyer()->create();
    $this->supplier = User::factory()->supplier()->create();
    $this->profile = $this->supplier->supplierProfile;
});

it('shows a verified supplier profile to buyers', function (): void {
    Review::factory()->create(['supplier_id' => $this->supplier->id, 'body' => 'Always on time.']);

    $this->actingAs($this->buyer)
        ->get(route('buyer.suppliers.show', $this->profile))
        ->assertOk()
        ->assertSee($this->profile->company_name)
        ->assertSee($this->profile->activity_description)
        ->assertSee('Always on time.');
});

it('never shows contact or registration details on the profile', function (): void {
    $this->actingAs($this->buyer)
        ->get(route('buyer.suppliers.show', $this->profile))
        ->assertOk()
        ->assertDontSee($this->supplier->phone)
        ->assertDontSee($this->supplier->email)
        ->assertDontSee($this->profile->commercial_reg_no)
        ->assertDontSee($this->profile->tax_number);
});

it('anonymises review authors', function (): void {
    $review = Review::factory()->create(['supplier_id' => $this->supplier->id]);

    $this->actingAs($this->buyer)
        ->get(route('buyer.suppliers.show', $this->profile))
        ->assertSee($review->anonymousAuthorLabel())
        ->assertDontSee($review->buyer->buyerProfile->company_name);
});

it('keeps suppliers and guests out of buyer-facing profiles', function (): void {
    $this->actingAs(User::factory()->supplier()->create())
        ->get(route('buyer.suppliers.show', $this->profile))
        ->assertRedirect()
        ->assertDontSee($this->profile->company_name);

    auth()->logout();

    $this->get(route('buyer.suppliers.show', $this->profile))->assertRedirect(route('login'));
});

it('hides suppliers that are not verified or are suspended', function (): void {
    $pending = User::factory()->supplier()->create();
    $pending->supplierProfile->update(['verification_status' => 'pending']);

    $suspended = User::factory()->supplier()->suspended()->create();

    $this->actingAs($this->buyer)->get(route('buyer.suppliers.show', $pending->supplierProfile))->assertNotFound();
    $this->actingAs($this->buyer)->get(route('buyer.suppliers.show', $suspended->supplierProfile))->assertNotFound();
});

it('links the message button only when the buyer already has a chat with the supplier', function (): void {
    $this->actingAs($this->buyer)
        ->get(route('buyer.suppliers.show', $this->profile))
        ->assertViewHas('conversation', null);

    $conversation = Conversation::factory()->create();
    $conversation->update(['buyer_id' => $this->buyer->id, 'supplier_id' => $this->supplier->id]);

    $this->actingAs($this->buyer)
        ->get(route('buyer.suppliers.show', $this->profile))
        ->assertSee(route('buyer.chats.show', $conversation));
});
