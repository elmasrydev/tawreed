<?php

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    seedReferenceData();
    Mail::fake();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contactPayload(array $overrides = []): array
{
    return [
        'name' => 'Omar Khaled',
        'email' => 'omar@example.com',
        'phone' => '010 1234 5678',
        'message' => 'Do you support suppliers outside Cairo?',
        ...$overrides,
    ];
}

it('shows the contact form at the bottom of the landing page', function (): void {
    $this->get(route('splash'))
        ->assertOk()
        ->assertSee('data-testid="contact-form"', false)
        ->assertSee(route('contact.store'), false)
        ->assertSee('href="#contact"', false);
});

it('emails the message to the admin inbox with the sender as reply-to', function (): void {
    $this->post(route('contact.store'), contactPayload())
        ->assertRedirect(route('splash').'#contact')
        ->assertSessionHas('status', __('landing.contact_sent'));

    Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail): bool => $mail->hasTo('samar.maher@tawreedhub.net')
        && $mail->hasReplyTo('omar@example.com', 'Omar Khaled')
        && $mail->messageBody === 'Do you support suppliers outside Cairo?');
});

it('renders the sender details in the email', function (): void {
    $mail = new ContactMessageMail('Omar Khaled', 'omar@example.com', '+201012345678', "Line one\nLine two");

    $mail->assertSeeInHtml('Omar Khaled')
        ->assertSeeInHtml('omar@example.com')
        ->assertSeeInHtml('+201012345678')
        ->assertSeeInHtml('Line one')
        ->assertSeeInHtml('Line two');
});

it('accepts a message without a phone number', function (): void {
    $this->post(route('contact.store'), contactPayload(['phone' => '']))
        ->assertSessionHasNoErrors();

    Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail): bool => $mail->phone === null);
});

it('rejects an invalid message and returns to the form', function (): void {
    $this->post(route('contact.store'), contactPayload(['name' => '', 'email' => 'not-an-email', 'phone' => '123', 'message' => 'short']))
        ->assertRedirect(route('splash').'#contact')
        ->assertSessionHasErrors(['name', 'email', 'phone', 'message']);

    Mail::assertNothingSent();
});

it('drops submissions that fill the honeypot', function (): void {
    $this->post(route('contact.store'), contactPayload(['website' => 'https://spam.example']))
        ->assertSessionHasErrors('website');

    Mail::assertNothingSent();
});

it('limits how often one visitor can send messages', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('contact.store'), contactPayload())->assertRedirect();
    }

    $this->post(route('contact.store'), contactPayload())->assertTooManyRequests();
});
