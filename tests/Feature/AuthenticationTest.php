<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedReferenceData();
});

it('offers both account types from the splash screen', function (): void {
    $this->get(route('splash'))
        ->assertOk()
        ->assertSee(__('landing.hero_cta_buyer'))
        ->assertSee(__('landing.hero_cta_supplier'));
});

it('shows the role chooser with a route to each registration form', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee(__('auth_ui.role_buyer_title'))
        ->assertSee(__('auth_ui.role_supplier_title'))
        ->assertSee(route('register.buyer'), false)
        ->assertSee(route('register.supplier'), false);
});

it('renders the login form with the email-or-phone field and no password reset link', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('auth_ui.login_title'))
        ->assertSee(__('ui.login_field'))
        ->assertSee('name="login"', false)
        ->assertSee('name="remember"', false)
        ->assertDontSee('password.request');
});

it('renders the buyer registration stepper with every field in one form', function (): void {
    $response = $this->get(route('register.buyer'))->assertOk();

    foreach (['name', 'job_title', 'phone', 'email', 'password', 'password_confirmation', 'company_name', 'company_address', 'business_type_id', 'governorate_id', 'commercial_reg_no', 'tax_card_no'] as $field) {
        $response->assertSee('name="'.$field.'"', false);
    }

    $response->assertSee(__('ui.anon_note'))
        ->assertSee(__('auth_ui.step_business'));

    expect(substr_count($response->getContent(), '<form method="POST" action="'.route('register.buyer').'"'))->toBe(1);
});

it('reopens the buyer stepper on the step that holds the first error', function (): void {
    $this->from(route('register.buyer'))
        ->followingRedirects()
        ->post(route('register.buyer'), ['name' => 'Ahmed'])
        ->assertSee('step: 1,', false);

    $this->from(route('register.buyer'))
        ->followingRedirects()
        ->post(route('register.buyer'), [
            'name' => 'Ahmed Samy',
            'phone' => '010 2345 6789',
            'email' => 'ahmed@cafecity.eg',
            'password' => 'Password!234',
            'password_confirmation' => 'Password!234',
        ])
        ->assertSee('step: 2,', false);
});

it('renders the supplier registration stepper as one multipart form', function (): void {
    $response = $this->get(route('register.supplier'))->assertOk();

    foreach (['governorate_ids[]', 'category_ids[]', 'documents[commercial_registration]', 'documents[tax_card]', 'documents[logo]', 'tax_number', 'facility_address', 'activity_description', 'payment_method'] as $field) {
        $response->assertSee('name="'.$field.'"', false);
    }

    $response->assertSee('enctype="multipart/form-data"', false)
        ->assertSee(__('auth_ui.step_documents'));
});

it('sends a signed in user straight to their own area', function (): void {
    $this->actingAs(User::factory()->buyer()->create())
        ->get(route('splash'))
        ->assertRedirect(route('buyer.home'));

    $this->actingAs(User::factory()->supplier()->create())
        ->get(route('splash'))
        ->assertRedirect(route('supplier.dashboard'));
});

it('registers a buyer, stores the profile and asks for phone verification', function (): void {
    $response = $this->post(route('register.buyer'), [
        'name' => 'Ahmed Samy',
        'job_title' => 'Purchasing Manager',
        'phone' => '010 2345 6789',
        'email' => 'ahmed@cafecity.eg',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'company_name' => 'Cafe City',
        'company_address' => 'New Cairo',
        'business_type_id' => BusinessType::where('slug', 'restaurant-cafe')->value('id'),
        'governorate_id' => Governorate::where('slug', 'cairo')->value('id'),
    ]);

    $response->assertRedirect(route('phone.verify'));

    $user = User::where('email', 'ahmed@cafecity.eg')->firstOrFail();

    expect($user->role)->toBe(UserRole::Buyer)
        ->and($user->phone)->toBe('01023456789')
        ->and($user->hasVerifiedPhone())->toBeFalse()
        ->and($user->buyerProfile->company_name)->toBe('Cafe City')
        ->and($user->otpCodes()->whereNull('consumed_at')->count())->toBe(1);

    $this->assertAuthenticatedAs($user);
});

it('rejects a buyer registration with a malformed egyptian phone', function (): void {
    $this->post(route('register.buyer'), [
        'name' => 'Ahmed Samy',
        'phone' => '0999999',
        'email' => 'ahmed@cafecity.eg',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'company_name' => 'Cafe City',
        'business_type_id' => BusinessType::first()->id,
        'governorate_id' => Governorate::first()->id,
    ])->assertSessionHasErrors('phone');

    expect(User::where('email', 'ahmed@cafecity.eg')->exists())->toBeFalse();
});

it('registers a supplier into the verification queue with their documents', function (): void {
    Storage::fake('public');

    $response = $this->post(route('register.supplier'), [
        'name' => 'Mahmoud El-Deltawy',
        'phone' => '011 8765 4321',
        'email' => 'sales@deltaroasters.eg',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'company_name' => 'Delta Coffee Roasters',
        'commercial_reg_no' => '445210',
        'tax_number' => '204-881-337',
        'facility_address' => 'Industrial zone, Obour',
        'governorate_ids' => Governorate::whereIn('slug', ['cairo', 'giza'])->pluck('id')->all(),
        'category_ids' => [Category::where('slug', 'beverages')->value('id')],
        'documents' => [
            'commercial_registration' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf'),
            'tax_card' => UploadedFile::fake()->create('tax.pdf', 100, 'application/pdf'),
        ],
    ]);

    $response->assertRedirect(route('phone.verify'));

    $profile = User::where('email', 'sales@deltaroasters.eg')->firstOrFail()->supplierProfile;

    expect($profile->verification_status)->toBe(VerificationStatus::Pending)
        ->and($profile->canSubmitQuotes())->toBeFalse()
        ->and($profile->governorates)->toHaveCount(2)
        ->and($profile->categories)->toHaveCount(1)
        ->and($profile->documents)->toHaveCount(2);
});

it('requires both statutory documents from a supplier', function (): void {
    $this->post(route('register.supplier'), [
        'name' => 'Mahmoud',
        'phone' => '01187654321',
        'email' => 'sales@deltaroasters.eg',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'company_name' => 'Delta Coffee Roasters',
        'commercial_reg_no' => '445210',
        'tax_number' => '204-881-337',
        'governorate_ids' => [Governorate::first()->id],
    ])->assertSessionHasErrors(['documents.commercial_registration', 'documents.tax_card']);
});

it('signs in with either an email address or a phone number', function (): void {
    $user = User::factory()->buyer()->create([
        'email' => 'ahmed@cafecity.eg',
        'phone' => '01023456789',
    ]);

    $this->post(route('login'), ['login' => 'ahmed@cafecity.eg', 'password' => 'password'])
        ->assertRedirect(route('buyer.home'));
    $this->assertAuthenticatedAs($user);

    auth()->logout();

    $this->post(route('login'), ['login' => '+20 10 2345 6789', 'password' => 'password'])
        ->assertRedirect(route('buyer.home'));
    $this->assertAuthenticatedAs($user);
});

it('refuses to sign in a suspended account', function (): void {
    User::factory()->buyer()->suspended()->create(['email' => 'blocked@cafecity.eg']);

    $this->post(route('login'), ['login' => 'blocked@cafecity.eg', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('sends an unverified phone to the verification screen after signing in', function (): void {
    $user = User::factory()->buyer()->unverified()->create(['email' => 'new@cafecity.eg']);

    $this->post(route('login'), ['login' => 'new@cafecity.eg', 'password' => 'password'])
        ->assertRedirect(route('phone.verify'));

    expect($user->fresh()->hasVerifiedPhone())->toBeFalse();
});

it('keeps a buyer out of the supplier area and vice versa', function (): void {
    $this->actingAs(User::factory()->buyer()->create())
        ->get(route('supplier.feed'))
        ->assertRedirect(route('buyer.home'));

    $this->actingAs(User::factory()->supplier()->create())
        ->get(route('buyer.home'))
        ->assertRedirect(route('supplier.dashboard'));
});

it('logs out a user who is suspended mid-session', function (): void {
    $user = User::factory()->buyer()->create();

    $this->actingAs($user)->get(route('buyer.home'))->assertOk();

    $user->update(['status' => UserStatus::Suspended]);

    $this->actingAs($user)->get(route('buyer.home'))->assertRedirect(route('login'));
});

it('remembers the chosen language on the account', function (): void {
    $user = User::factory()->buyer()->create(['locale' => 'ar']);

    $this->actingAs($user)
        ->from(route('buyer.home'))
        ->patch(route('locale.update', 'en'))
        ->assertRedirect(route('buyer.home'));

    expect($user->fresh()->locale)->toBe('en');
});

it('rejects an unsupported language', function (): void {
    $this->actingAs(User::factory()->buyer()->create())
        ->patch(route('locale.update', 'fr'))
        ->assertNotFound();
});
