<?php

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Models\Category;
use App\Models\Testimonial;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();

    $this->admin = User::factory()->admin()->create();
});

it('reads the quote in the current language and falls back to the other one', function (): void {
    $testimonial = Testimonial::factory()->create([
        'quote_ar' => 'نص عربي',
        'quote_en' => 'English text',
        'author_role_ar' => null,
        'author_role_en' => 'Purchasing manager',
    ]);

    app()->setLocale('ar');
    expect($testimonial->quote)->toBe('نص عربي')
        ->and($testimonial->author_role)->toBe('Purchasing manager');

    app()->setLocale('en');
    expect($testimonial->quote)->toBe('English text');
});

it('orders published testimonials by sort and skips drafts', function (): void {
    Testimonial::factory()->create(['author_name' => 'B', 'sort' => 2]);
    Testimonial::factory()->create(['author_name' => 'A', 'sort' => 1]);
    Testimonial::factory()->unpublished()->create(['author_name' => 'Draft']);

    expect(Testimonial::query()->published()->pluck('author_name')->all())->toBe(['A', 'B']);
});

it('lets an admin list and create testimonials', function (): void {
    $existing = Testimonial::factory()->create(['author_name' => 'Nour Hassan']);

    Livewire::actingAs($this->admin)
        ->test(ListTestimonials::class)
        ->assertCanSeeTableRecords([$existing])
        ->callAction(TestAction::make('create')->table(), [
            'author_name' => 'Omar Adel',
            'author_role_en' => 'Owner, bakery',
            'quote_ar' => 'منصة ممتازة',
            'quote_en' => 'A great platform',
            'rating' => 5,
            'sort' => 1,
            'is_published' => true,
        ])
        ->assertHasNoFormErrors();

    expect(Testimonial::query()->where('author_name', 'Omar Adel')->first())
        ->is_published->toBeTrue()
        ->rating->toBe(5);
});

it('keeps non-admins out of the testimonials screen', function (): void {
    $this->actingAs(User::factory()->buyer()->create())
        ->get(ListTestimonials::getUrl())
        ->assertForbidden();
});

it('lets an admin upload a category image', function (): void {
    Storage::fake('public');

    $category = Category::query()->where('slug', 'food')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ListCategories::class)
        ->callAction(TestAction::make('edit')->table($category), [
            'image_path' => UploadedFile::fake()->image('food.jpg', 1600, 900),
        ])
        ->assertHasNoFormErrors();

    $path = $category->fresh()->image_path;

    expect($path)->toStartWith('categories/');
    Storage::disk('public')->assertExists($path);
});
