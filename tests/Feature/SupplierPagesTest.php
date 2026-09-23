<?php

use App\Enums\UserRole;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Governorate;
use App\Models\Quote;
use App\Models\RejectionReason;
use App\Models\Rfq;
use App\Models\Subscription;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedReferenceData();

    $this->supplier = User::factory()->supplier()->create();
    $this->cairo = Governorate::where('slug', 'cairo')->firstOrFail();
    $this->giza = Governorate::where('slug', 'giza')->firstOrFail();
    $this->beverages = Category::where('slug', 'beverages')->firstOrFail();
    $this->cement = Category::where('slug', 'building-materials')->firstOrFail();
});

/**
 * A supplier that has not been verified yet.
 */
function pendingSupplier(): User
{
    return User::factory()
        ->has(SupplierProfile::factory()->pending(), 'supplierProfile')
        ->create(['role' => UserRole::Supplier]);
}

it('counts only open requests in the supplier categories and coverage on the dashboard', function (): void {
    $this->supplier->supplierProfile->categories()->attach($this->beverages);
    $this->supplier->supplierProfile->governorates()->attach($this->cairo);

    Rfq::factory()->create(['title' => 'Matching espresso', 'category_id' => $this->beverages->id, 'governorate_id' => $this->cairo->id]);
    Rfq::factory()->create(['title' => 'Wrong area espresso', 'category_id' => $this->beverages->id, 'governorate_id' => $this->giza->id]);
    Rfq::factory()->create(['title' => 'Wrong category cement', 'category_id' => $this->cement->id, 'governorate_id' => $this->cairo->id]);
    Rfq::factory()->expired()->create(['category_id' => $this->beverages->id, 'governorate_id' => $this->cairo->id]);

    $this->supplier->update(['locale' => 'en']);

    $this->actingAs($this->supplier)
        ->get(route('supplier.dashboard'))
        ->assertOk()
        ->assertSee('1 open RFQ matches your categories and areas')
        ->assertSee('Matching espresso')
        ->assertDontSee('Wrong area espresso')
        ->assertDontSee('Wrong category cement');
});

it('matches on coverage alone when the supplier has no categories', function (): void {
    $this->supplier->supplierProfile->governorates()->attach($this->cairo);

    Rfq::factory()->create(['title' => 'Cairo cement', 'category_id' => $this->cement->id, 'governorate_id' => $this->cairo->id]);
    Rfq::factory()->create(['title' => 'Giza cement', 'category_id' => $this->cement->id, 'governorate_id' => $this->giza->id]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.dashboard'))
        ->assertSee('Cairo cement')
        ->assertDontSee('Giza cement');
});

it('derives the win rate and won value from decided quotes only', function (): void {
    Quote::factory()->selected()->create(['supplier_id' => $this->supplier->id, 'total_price' => 10000, 'delivery_cost' => 500]);
    Quote::factory()->rejected()->create(['supplier_id' => $this->supplier->id]);
    Quote::factory()->create(['supplier_id' => $this->supplier->id]);

    $this->supplier->update(['locale' => 'en']);

    $this->actingAs($this->supplier)
        ->get(route('supplier.dashboard'))
        ->assertOk()
        ->assertSee('50%')
        ->assertSee('1 of 2 decided quotes')
        ->assertSee('EGP 10,500 won in total');
});

it('never shows the buyer identity on the dashboard', function (): void {
    $buyer = User::factory()->buyer()->create(['name' => 'Ahmed Samy']);
    $buyer->buyerProfile->update([
        'company_name' => 'Cafe City',
        'business_type_id' => BusinessType::where('slug', 'restaurant-cafe')->value('id'),
    ]);
    $rfq = Rfq::factory()->for($buyer, 'buyer')->create(['governorate_id' => $this->cairo->id]);
    Quote::factory()->for($rfq)->create(['supplier_id' => $this->supplier->id]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.dashboard'))
        ->assertOk()
        ->assertSee($rfq->title)
        ->assertDontSee('Cafe City')
        ->assertDontSee('Ahmed Samy')
        ->assertDontSee($buyer->phone);
});

it('embeds the quote drawer on the request page when the supplier can quote', function (): void {
    $rfq = Rfq::factory()->create();

    $this->actingAs($this->supplier)
        ->get(route('supplier.rfqs.show', $rfq))
        ->assertOk()
        ->assertSee('open-drawer', false)
        ->assertSeeLivewire('supplier.submit-quote');
});

it('shows the verification gate instead of the drawer to an unverified supplier', function (): void {
    $rfq = Rfq::factory()->create();

    $this->actingAs(pendingSupplier())
        ->get(route('supplier.rfqs.show', $rfq))
        ->assertOk()
        ->assertDontSeeLivewire('supplier.submit-quote')
        ->assertSee(__('supplier.gate_review'))
        ->assertSee(route('supplier.account', ['tab' => 'documents']), false);
});

it('shows the supplier their own quote instead of the drawer once quoted', function (): void {
    $rfq = Rfq::factory()->create();
    Quote::factory()->for($rfq)->create(['supplier_id' => $this->supplier->id, 'total_price' => 92500]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.rfqs.show', $rfq))
        ->assertOk()
        ->assertDontSeeLivewire('supplier.submit-quote')
        ->assertSee(__('supplier.your_quote'))
        ->assertSee('92,500.00');
});

it('keeps the full page quote form working as a deep link', function (): void {
    $rfq = Rfq::factory()->create();

    $this->actingAs($this->supplier)
        ->get(route('supplier.rfqs.quote', $rfq))
        ->assertOk()
        ->assertSee(__('ui.send_quote'))
        ->assertSee(__('quote.back_to_request'));
});

it('serves a request attachment only for that request', function (): void {
    Storage::fake('public');

    $rfq = Rfq::factory()->create();
    $media = $rfq->addMedia(UploadedFile::fake()->create('spec-sheet.pdf', 12, 'application/pdf'))->toMediaCollection('attachments');
    $other = Rfq::factory()->create();

    $this->actingAs($this->supplier)
        ->get(route('supplier.rfqs.attachment', [$rfq, $media]))
        ->assertOk();

    $this->actingAs($this->supplier)
        ->get(route('supplier.rfqs.attachment', [$other, $media]))
        ->assertNotFound();

    $this->actingAs($this->supplier)
        ->get(route('supplier.rfqs.show', $rfq))
        ->assertSee('spec-sheet.pdf');
});

it('refuses attachments of a request that is no longer open', function (): void {
    Storage::fake('public');

    $rfq = Rfq::factory()->awarded()->create();
    $media = $rfq->addMedia(UploadedFile::fake()->create('spec-sheet.pdf', 12, 'application/pdf'))->toMediaCollection('attachments');

    $this->actingAs($this->supplier)
        ->get(route('supplier.rfqs.attachment', [$rfq, $media]))
        ->assertForbidden();
});

it('filters my quotes by status tab and labels auto-closed quotes as not selected', function (): void {
    Quote::factory()->create(['supplier_id' => $this->supplier->id, 'rfq_id' => Rfq::factory()->create(['title' => 'Pending sugar'])->id]);
    Quote::factory()->rejected()->create(['supplier_id' => $this->supplier->id, 'rfq_id' => Rfq::factory()->create(['title' => 'Closed flour'])->id]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.quotes.index', ['status' => 'rejected']))
        ->assertOk()
        ->assertSee('Closed flour')
        ->assertSee(__('enums.quote_status.not_selected'))
        ->assertDontSee('Pending sugar');
});

it('shows the rejection reason under a rejected quote', function (): void {
    $reason = RejectionReason::query()->firstOrFail();
    Quote::factory()->rejected()->create(['supplier_id' => $this->supplier->id, 'rejection_reason_id' => $reason->id]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.quotes.index'))
        ->assertSee(__('supplier.reason', ['reason' => $reason->name]));
});

it('locks the chat of a selected quote until the supplier subscribes', function (): void {
    $quote = Quote::factory()->selected()->create(['supplier_id' => $this->supplier->id]);
    $conversation = Conversation::factory()->create([
        'rfq_id' => $quote->rfq_id,
        'quote_id' => $quote->id,
        'buyer_id' => $quote->rfq->buyer_id,
        'supplier_id' => $this->supplier->id,
    ]);

    $this->actingAs($this->supplier)
        ->get(route('supplier.quotes.index'))
        ->assertSee(__('supplier.subscribe_to_unlock'))
        ->assertDontSee(route('supplier.chats.show', $conversation), false);

    Subscription::factory()->create(['supplier_id' => $this->supplier->id]);

    $this->actingAs($this->supplier->fresh())
        ->get(route('supplier.quotes.index'))
        ->assertSee(route('supplier.chats.show', $conversation), false);
});

it('opens the documents tab of the profile from the banner link', function (): void {
    $this->actingAs(pendingSupplier())
        ->get(route('supplier.account', ['tab' => 'documents']))
        ->assertOk()
        ->assertSee(__('supplier.verification_documents'))
        ->assertSee(route('logout'), false);
});
