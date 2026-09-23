<?php

use App\Models\User;

it('serves the splash screen', function (): void {
    $this->get('/')->assertOk();
});

it('answers the health check', function (): void {
    $this->get('/up')->assertOk();
});

it('renders the admin login screen', function (): void {
    $this->get(route('filament.admin.auth.login'))->assertOk();
});

it('keeps non-admins out of the admin panel', function (): void {
    seedReferenceData();

    $this->actingAs(User::factory()->buyer()->create())
        ->get('/admin')
        ->assertForbidden();
});
