<?php

use App\Enums\RfqStatus;
use App\Http\Controllers\SplashController;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Rfq;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedReferenceData();
});

it('renders the landing page for guests with links into registration', function (): void {
    $this->get(route('splash'))
        ->assertOk()
        ->assertSee(__('landing.hero_cta_buyer'))
        ->assertSee(__('landing.hero_cta_supplier'))
        ->assertSee(route('register.buyer'), false)
        ->assertSee(route('register.supplier'), false)
        ->assertSee(route('filament.admin.auth.login'), false);
});

it('never renders the hidden trust strip numbers', function (): void {
    $this->get(route('splash'))
        ->assertOk()
        ->assertDontSee('1,240+')
        ->assertDontSee('EGP 48M');
});

it('lists the active plans from the database and highlights the best value', function (): void {
    Plan::query()->where('slug', 'monthly')->update(['is_active' => false]);

    $response = $this->get(route('splash'))->assertOk();

    $response->assertSee('data-plan="yearly"', false)
        ->assertSee('data-plan="trial-15-days"', false)
        ->assertDontSee('data-plan="monthly"', false)
        ->assertSee(__('landing.best_value'))
        ->assertSee(__('landing.plan_trial', ['days' => 7]));

    expect(substr_count($response->getContent(), 'data-best-value'))->toBe(1);
});

it('hides pricing and prices when there are no active plans', function (): void {
    Plan::query()->update(['is_active' => false]);

    $this->get(route('splash'))
        ->assertOk()
        ->assertDontSee('id="pricing"', false)
        ->assertDontSee('href="#pricing"', false)
        ->assertSee(__('landing.faq_4_a'));
});

it('hides the testimonials section until one is published', function (): void {
    Testimonial::factory()->unpublished()->create(['author_name' => 'Hidden Author']);

    $this->get(route('splash'))
        ->assertOk()
        ->assertDontSee('data-testid="landing-testimonials"', false)
        ->assertDontSee('Hidden Author');
});

it('shows only published testimonials, at most three, in order', function (): void {
    Testimonial::factory()->create(['author_name' => 'Second Author', 'sort' => 2]);
    Testimonial::factory()->create(['author_name' => 'First Author', 'sort' => 1]);
    Testimonial::factory()->create(['author_name' => 'Third Author', 'sort' => 3]);
    Testimonial::factory()->create(['author_name' => 'Fourth Author', 'sort' => 4]);
    Testimonial::factory()->unpublished()->create(['author_name' => 'Draft Author', 'sort' => 0]);

    $this->get(route('splash'))
        ->assertOk()
        ->assertSee('data-testid="landing-testimonials"', false)
        ->assertSeeInOrder(['First Author', 'Second Author', 'Third Author'])
        ->assertDontSee('Fourth Author')
        ->assertDontSee('Draft Author');
});

it('shows real main categories with their image and open request count', function (): void {
    Storage::fake('public');

    $food = Category::query()->where('slug', 'food')->firstOrFail();
    $food->update(['image_path' => 'categories/food.webp']);
    Category::query()->where('slug', 'textiles')->update(['is_active' => false]);

    Rfq::factory()->count(2)->create(['category_id' => $food->id]);
    Rfq::factory()->expired()->create(['category_id' => $food->id]);

    $this->get(route('splash'))
        ->assertOk()
        ->assertSee('data-testid="landing-categories"', false)
        ->assertSee($food->name)
        ->assertSee(Storage::disk('public')->url('categories/food.webp'), false)
        ->assertSee(trans_choice('landing.category_open_rfqs', 2))
        ->assertSee('placeholder-stripes', false)
        ->assertDontSee(Category::query()->where('slug', 'textiles')->value('name_ar'));
});

it('hides the categories section when there are no main categories', function (): void {
    Category::query()->update(['is_active' => false]);

    $this->get(route('splash'))
        ->assertOk()
        ->assertDontSee('data-testid="landing-categories"', false);
});

it('shows the eight busiest categories first and the rest behind view all', function (): void {
    $other = Category::query()->where('slug', 'other')->firstOrFail();
    Rfq::factory()->create(['category_id' => $other->id]);

    $response = $this->get(route('splash'))->assertOk();
    $html = $response->getContent();

    expect(Category::query()->main()->count())->toBeGreaterThan(SplashController::VISIBLE_CATEGORIES)
        ->and(strpos($html, e($other->name)))->toBeLessThan(strpos($html, e(Category::query()->where('slug', 'food')->value('name_ar'))))
        ->and(substr_count($html, 'x-show="showAll"'))->toBe(Category::query()->main()->count() - SplashController::VISIBLE_CATEGORIES);

    $response->assertSee(__('landing.categories_view_all'));
});

it('no longer markets anonymity or vat comparison', function (): void {
    $this->get(route('splash'))
        ->assertOk()
        ->assertDontSee('مجهول')
        ->assertDontSee('الضريبة')
        ->assertSee(__('landing.trust_verified'))
        ->assertSee(__('landing.step_1_title'));

    $this->get(route('login'))->assertOk()->assertDontSee('مجهول');
});

it('shows the buyer tagline on the buyer registration panel', function (): void {
    $this->get(route('register.buyer'))
        ->assertOk()
        ->assertSee(__('auth_ui.panel_buyer_tagline'))
        ->assertDontSee('هويتك');
});

it('shows the latest open request without revealing who posted it', function (): void {
    $buyer = User::factory()->buyer()->create(['name' => 'Karim Fathy', 'email' => 'karim@secret-hotel.eg']);
    $buyer->buyerProfile->update(['company_name' => 'Secret Hotel Group', 'company_address' => '12 Hidden Street']);

    Rfq::factory()->for($buyer, 'buyer')->create([
        'title' => 'Frozen chicken breast',
        'published_at' => now(),
    ]);

    $this->get(route('splash'))
        ->assertOk()
        ->assertSee('data-testid="featured-rfq"', false)
        ->assertSee('Frozen chicken breast')
        ->assertSee($buyer->buyerProfile->businessType->name)
        ->assertDontSee('Secret Hotel Group')
        ->assertDontSee('Karim Fathy')
        ->assertDontSee('karim@secret-hotel.eg')
        ->assertDontSee('12 Hidden Street')
        ->assertDontSee($buyer->phone);
});

it('hides the request card when no request is open', function (): void {
    Rfq::factory()->create(['status' => RfqStatus::Awarded, 'title' => 'Awarded request']);

    $this->get(route('splash'))
        ->assertOk()
        ->assertDontSee('data-testid="featured-rfq"', false)
        ->assertDontSee('Awarded request');
});

it('renders in english when the guest picks it', function (): void {
    $this->withSession(['locale' => 'en'])
        ->get(route('splash'))
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr">', false)
        ->assertSee('Request your supplies and compare competitive supplier quotes directly');
});

it('lists the five busiest categories in the nav menu, then all categories', function (): void {
    $other = Category::query()->where('slug', 'other')->firstOrFail();
    Rfq::factory()->count(2)->create(['category_id' => $other->id]);

    $html = $this->get(route('splash'))
        ->assertOk()
        ->assertSee('data-testid="nav-categories"', false)
        ->assertSee(route('categories.index'), false)
        ->getContent();

    $menu = str($html)->after('id="landing-categories-menu"')->before('</div>')->toString();

    expect(substr_count($menu, route('rfqs.index').'?category='))->toBe(SplashController::MENU_CATEGORIES)
        ->and($menu)->toContain(route('rfqs.index', ['category' => 'other']))
        ->and(strpos($menu, 'category=other'))->toBeLessThan(strpos($menu, 'category=food'));
});

it('puts a search bar for open requests above the hero', function (): void {
    $html = $this->get(route('splash'))
        ->assertOk()
        ->assertSee('data-testid="landing-search"', false)
        ->assertSee('action="'.route('rfqs.index').'"', false)
        ->getContent();

    expect(strpos($html, 'data-testid="landing-search"'))->toBeLessThan(strpos($html, __('landing.hero_title')));
});

it('lists the most recent open requests under the hero', function (): void {
    Rfq::factory()->create(['title' => 'Oldest open request', 'published_at' => now()->subDays(20)]);
    Rfq::factory()->count(SplashController::RECENT_RFQS)->create(['published_at' => now()->subDay()]);
    Rfq::factory()->expired()->create(['title' => 'Expired request']);

    $response = $this->get(route('splash'))
        ->assertOk()
        ->assertSee('data-testid="recent-rfqs"', false)
        ->assertSee(route('rfqs.index'), false)
        ->assertDontSee('Oldest open request')
        ->assertDontSee('Expired request');

    expect(substr_count($response->getContent(), 'data-testid="public-rfq"'))->toBe(SplashController::RECENT_RFQS);
});

it('hides the recent requests section when none is open', function (): void {
    $this->get(route('splash'))
        ->assertOk()
        ->assertDontSee('data-testid="recent-rfqs"', false);
});
