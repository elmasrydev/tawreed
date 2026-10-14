<?php

use App\Filament\Resources\SupplierProfiles\Pages\ViewSupplierProfile;
use App\Filament\Resources\SupplierProfiles\RelationManagers\AwardedRfqsRelationManager;
use App\Filament\Resources\SupplierProfiles\RelationManagers\PortfolioItemsRelationManager;
use App\Models\PortfolioItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();
    Storage::fake('public');

    $this->supplier = User::factory()->supplier()->create();
    $this->profile = $this->supplier->supplierProfile;
});

function portfolioItemWithImage(User $supplier, array $attributes = []): PortfolioItem
{
    $item = PortfolioItem::factory()->create(['supplier_profile_id' => $supplier->supplierProfile->id, ...$attributes]);
    $item->addMedia(UploadedFile::fake()->image('work.jpg'))->toMediaCollection('images');

    return $item;
}

it('lets a supplier add a portfolio project with images', function (): void {
    $this->actingAs($this->supplier)
        ->post(route('supplier.portfolio.store'), [
            'title' => 'Kitchen fit-out for a Cairo hotel',
            'description' => 'Supplied all stainless steel equipment.',
            'client_type' => 'Hotel',
            'year' => 2025,
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ])
        ->assertRedirect(route('supplier.account', ['tab' => 'portfolio']));

    $item = $this->profile->portfolioItems()->sole();

    expect($item->title)->toBe('Kitchen fit-out for a Cairo hotel')
        ->and($item->year)->toBe(2025)
        ->and($item->getMedia('images'))->toHaveCount(2);
});

it('requires a title and at least one image for a new project', function (): void {
    $this->actingAs($this->supplier)
        ->post(route('supplier.portfolio.store'), ['title' => ''])
        ->assertSessionHasErrors(['title', 'images']);

    $this->actingAs($this->supplier)
        ->post(route('supplier.portfolio.store'), [
            'title' => 'Too many images',
            'images' => array_map(fn (int $i) => UploadedFile::fake()->image("{$i}.jpg"), range(1, PortfolioItem::MAX_IMAGES + 1)),
        ])
        ->assertSessionHasErrors('images');

    expect($this->profile->portfolioItems()->count())->toBe(0);
});

it('lets a supplier edit a project, removing and adding images', function (): void {
    $item = portfolioItemWithImage($this->supplier);
    $oldImage = $item->getFirstMedia('images');

    $this->actingAs($this->supplier)
        ->put(route('supplier.portfolio.update', $item), [
            'title' => 'Renamed project',
            'remove_images' => [$oldImage->id],
            'images' => [UploadedFile::fake()->image('new.jpg')],
        ])
        ->assertRedirect(route('supplier.account', ['tab' => 'portfolio']));

    $item->refresh();

    expect($item->title)->toBe('Renamed project')
        ->and($item->getMedia('images'))->toHaveCount(1)
        ->and($item->getFirstMedia('images')->is($oldImage))->toBeFalse();
});

it('lets a supplier delete a project', function (): void {
    $item = portfolioItemWithImage($this->supplier);

    $this->actingAs($this->supplier)
        ->delete(route('supplier.portfolio.destroy', $item))
        ->assertRedirect(route('supplier.account', ['tab' => 'portfolio']));

    expect(PortfolioItem::query()->count())->toBe(0);
});

it('stops suppliers from changing another supplier\'s portfolio', function (): void {
    $item = portfolioItemWithImage(User::factory()->supplier()->create());

    $this->actingAs($this->supplier)
        ->put(route('supplier.portfolio.update', $item), ['title' => 'Hijacked'])
        ->assertForbidden();

    $this->actingAs($this->supplier)
        ->delete(route('supplier.portfolio.destroy', $item))
        ->assertForbidden();

    expect($item->fresh()->title)->not->toBe('Hijacked');
});

it('shows the supplier their projects on the portfolio tab', function (): void {
    portfolioItemWithImage($this->supplier, ['title' => 'Bakery ovens for Alexandria']);

    $this->actingAs($this->supplier)
        ->get(route('supplier.account', ['tab' => 'portfolio']))
        ->assertOk()
        ->assertSee('Bakery ovens for Alexandria')
        ->assertSee(__('supplier.portfolio_add'));
});

it('shows buyers the portfolio and successful deals on the supplier profile', function (): void {
    $buyer = User::factory()->buyer()->create();
    $viewer = User::factory()->buyer()->create();
    portfolioItemWithImage($this->supplier, ['title' => 'Coffee machines for a café chain']);

    $deal = awardedDeal($buyer, $this->supplier, ['title' => 'Arabica beans, monthly']);
    Review::factory()->create(['supplier_id' => $this->supplier->id, 'buyer_id' => $buyer->id, 'rfq_id' => $deal->id, 'body' => 'Delivered early.']);
    awardedDeal($buyer, User::factory()->supplier()->create(), ['title' => 'Someone else\'s deal']);

    $this->actingAs($viewer)
        ->get(route('buyer.suppliers.show', $this->profile))
        ->assertOk()
        ->assertSee('Coffee machines for a café chain')
        ->assertSee(__('buyer.successful_deals'))
        ->assertSee('Arabica beans, monthly')
        ->assertSee($deal->anonymousBuyerLabel())
        ->assertSee('Delivered early.')
        ->assertDontSee('Someone else\'s deal')
        ->assertDontSee($buyer->buyerProfile->company_name)
        ->assertDontSee(number_format($deal->awardedQuote->grandTotal(), 2));
});

it('hides the deals section when the supplier has none', function (): void {
    $this->actingAs(User::factory()->buyer()->create())
        ->get(route('buyer.suppliers.show', $this->profile))
        ->assertOk()
        ->assertDontSee(__('buyer.successful_deals'));
});

it('shows admins the supplier\'s portfolio and successful deals', function (): void {
    $admin = User::factory()->admin()->create();
    $item = portfolioItemWithImage($this->supplier);
    $deal = awardedDeal(User::factory()->buyer()->create(), $this->supplier);

    $this->actingAs($admin)
        ->get(ViewSupplierProfile::getUrl(['record' => $this->profile]))
        ->assertOk();

    Livewire::actingAs($admin)
        ->test(PortfolioItemsRelationManager::class, ['ownerRecord' => $this->profile, 'pageClass' => ViewSupplierProfile::class])
        ->assertCanSeeTableRecords([$item]);

    Livewire::actingAs($admin)
        ->test(AwardedRfqsRelationManager::class, ['ownerRecord' => $this->profile, 'pageClass' => ViewSupplierProfile::class])
        ->assertCanSeeTableRecords([$deal]);
});
