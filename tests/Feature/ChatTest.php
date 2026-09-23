<?php

use App\Actions\Chat\SendMessage;
use App\Actions\Quote\SelectQuote;
use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();

    $this->buyer = User::factory()->buyer()->create();
    $this->supplier = User::factory()->supplier()->create();
    Subscription::factory()->create(['supplier_id' => $this->supplier->id]);

    $rfq = Rfq::factory()->for($this->buyer, 'buyer')->create();
    $quote = Quote::factory()->for($rfq)->create(['supplier_id' => $this->supplier->id]);

    $this->conversation = app(SelectQuote::class)->handle($quote);
    $this->supplier = $this->supplier->fresh();
});

it('opens the thread with a system notice explaining the reveal', function (): void {
    $first = $this->conversation->messages()->oldest()->first();

    expect($first->type)->toBe(MessageType::System)
        ->and($first->body)->toBe(__('chat.opened_notice'))
        ->and($first->sender_id)->toBeNull();
});

it('shows each side the other contact details once the deal is awarded', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('chat.thread', ['conversation' => $this->conversation])
        ->assertSee($this->supplier->email)
        ->assertSee($this->supplier->phone);

    Livewire::actingAs($this->supplier)
        ->test('chat.thread', ['conversation' => $this->conversation])
        ->assertSee($this->buyer->email)
        ->assertSee($this->buyer->phone);
});

it('sends a message from either side', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('chat.thread', ['conversation' => $this->conversation])
        ->set('draft', 'Could we get a 2kg sample first?')
        ->call('send')
        ->assertSet('draft', '');

    Livewire::actingAs($this->supplier)
        ->test('chat.thread', ['conversation' => $this->conversation])
        ->set('draft', 'Yes, it ships tomorrow.')
        ->call('send');

    $bodies = $this->conversation->messages()->oldest()->pluck('body');

    expect($bodies)->toContain('Could we get a 2kg sample first?')
        ->and($bodies)->toContain('Yes, it ships tomorrow.');
});

it('refuses an empty message', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('chat.thread', ['conversation' => $this->conversation])
        ->set('draft', '')
        ->call('send')
        ->assertHasErrors('draft');
});

it('posts the sample request as its own message type', function (): void {
    Livewire::actingAs($this->buyer)
        ->test('chat.thread', ['conversation' => $this->conversation])
        ->call('requestSample');

    $message = $this->conversation->messages()->latest('id')->first();

    expect($message->type)->toBe(MessageType::SampleRequest)
        ->and($message->sender_id)->toBe($this->buyer->id);
});

it('advances the conversation timestamp when a message is sent', function (): void {
    $before = $this->conversation->last_message_at;

    $this->travel(2)->minutes();

    Livewire::actingAs($this->buyer)
        ->test('chat.thread', ['conversation' => $this->conversation])
        ->set('draft', 'Any update?')
        ->call('send');

    expect($this->conversation->fresh()->last_message_at->greaterThan($before))->toBeTrue();
});

it('marks the other side messages as read on open', function (): void {
    Livewire::actingAs($this->supplier)
        ->test('chat.thread', ['conversation' => $this->conversation])
        ->set('draft', 'Hello')
        ->call('send');

    expect($this->conversation->unreadCountFor($this->buyer))->toBe(1);

    Livewire::actingAs($this->buyer)->test('chat.thread', ['conversation' => $this->conversation]);

    expect($this->conversation->fresh()->unreadCountFor($this->buyer))->toBe(0);
});

it('keeps a stranger out of the thread', function (): void {
    $stranger = User::factory()->buyer()->create();

    expect(Gate::forUser($stranger)->allows('view', $this->conversation))->toBeFalse();

    $this->actingAs($stranger)
        ->get(route('buyer.chats.show', $this->conversation))
        ->assertForbidden();
});

it('locks the thread for a supplier whose plan has lapsed', function (): void {
    $this->supplier->subscriptions()->update(['ends_at' => now()->subDay()]);
    $lapsed = $this->supplier->fresh();

    expect($lapsed->hasActiveSubscription())->toBeFalse()
        ->and(Gate::forUser($lapsed)->allows('view', $this->conversation))->toBeFalse();

    $this->actingAs($lapsed)
        ->get(route('supplier.chats.show', $this->conversation))
        ->assertRedirect(route('supplier.subscription'));
});

it('never locks the buyer out of their own thread', function (): void {
    expect(Gate::forUser($this->buyer)->allows('view', $this->conversation))->toBeTrue();

    $this->actingAs($this->buyer)
        ->get(route('buyer.chats.show', $this->conversation))
        ->assertOk();
});

it('lists the conversation for both sides', function (): void {
    $this->actingAs($this->buyer)
        ->get(route('buyer.chats.index'))
        ->assertOk()
        ->assertSee($this->supplier->supplierProfile->company_name);

    $this->actingAs($this->supplier)
        ->get(route('supplier.chats.index'))
        ->assertOk()
        ->assertSee($this->buyer->buyerProfile->company_name);
});

it('shows an empty state before any quote is selected', function (): void {
    $freshBuyer = User::factory()->buyer()->create();

    $this->actingAs($freshBuyer)
        ->get(route('buyer.chats.index'))
        ->assertOk()
        ->assertSee(__('chat.no_chats'));

    expect(Conversation::query()->forParticipant($freshBuyer)->count())->toBe(0);
});

it('lists the participant\'s other conversations beside the open thread', function (): void {
    $otherSupplier = User::factory()->supplier()->create();
    Subscription::factory()->create(['supplier_id' => $otherSupplier->id]);

    $otherRfq = Rfq::factory()->for($this->buyer, 'buyer')->create(['title' => 'Basmati rice for the kitchen']);
    $otherQuote = Quote::factory()->for($otherRfq)->create(['supplier_id' => $otherSupplier->id]);
    app(SelectQuote::class)->handle($otherQuote);

    $this->actingAs($this->buyer)
        ->get(route('buyer.chats.show', $this->conversation))
        ->assertOk()
        ->assertSee($otherSupplier->fresh()->supplierProfile->company_name)
        ->assertSee('Basmati rice for the kitchen')
        ->assertSee(route('buyer.chats.show', Conversation::query()->where('rfq_id', $otherRfq->id)->first()));
});

it('never lists someone else\'s conversation', function (): void {
    $strangerBuyer = User::factory()->buyer()->create();
    $strangerSupplier = User::factory()->supplier()->create();
    Subscription::factory()->create(['supplier_id' => $strangerSupplier->id]);

    $strangerRfq = Rfq::factory()->for($strangerBuyer, 'buyer')->create(['title' => 'Private stranger request']);
    $strangerQuote = Quote::factory()->for($strangerRfq)->create(['supplier_id' => $strangerSupplier->id]);
    $strangerConversation = app(SelectQuote::class)->handle($strangerQuote);

    $this->actingAs($this->buyer)
        ->get(route('buyer.chats.show', $this->conversation))
        ->assertOk()
        ->assertDontSee('Private stranger request')
        ->assertDontSee(route('buyer.chats.show', $strangerConversation));

    $this->actingAs($this->supplier)
        ->get(route('supplier.chats.index'))
        ->assertOk()
        ->assertDontSee('Private stranger request')
        ->assertDontSee($strangerBuyer->buyerProfile->company_name);
});

it('shows the unread count on threads the viewer has not opened', function (): void {
    foreach (['First', 'Second', 'Third'] as $body) {
        app(SendMessage::class)->handle($this->conversation, $this->supplier, $body);
    }

    $this->actingAs($this->buyer)
        ->get(route('buyer.chats.index'))
        ->assertOk()
        ->assertSee('data-unread="3"', false)
        ->assertSee(trans_choice('chat.unread_count', 3, ['count' => 3]));

    $this->actingAs($this->supplier)
        ->get(route('supplier.chats.index'))
        ->assertOk()
        ->assertDontSee('data-unread=', false);
});
