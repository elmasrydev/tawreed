<?php

use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use App\Services\OtpService;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->otp = app(OtpService::class);
    $this->user = User::factory()->unverified()->create();
});

it('issues a six digit code that expires in ten minutes', function (): void {
    $code = $this->otp->issue($this->user);

    expect($code->code)->toHaveLength(OtpService::CODE_LENGTH)
        ->and($code->code)->toMatch('/^\d{6}$/')
        ->and($code->expires_at->diffInMinutes(now(), absolute: true))->toBeLessThanOrEqual(10)
        ->and($code->isUsable())->toBeTrue();
});

it('emails the code to the user', function (): void {
    Notification::fake();

    $code = $this->otp->issue($this->user);

    Notification::assertSentTo(
        $this->user,
        VerificationCodeNotification::class,
        fn (VerificationCodeNotification $notification): bool => $notification->otpCode->is($code)
            && str_contains($notification->toMail($this->user)->render(), $code->code),
    );
});

it('still issues a code when the mail provider fails', function (): void {
    Notification::shouldReceive('send')->andThrow(new RuntimeException('Mail provider down'));

    $code = $this->otp->issue($this->user);

    expect($code->exists)->toBeTrue()
        ->and($code->isUsable())->toBeTrue();
});

it('verifies the email when the correct code is supplied', function (): void {
    $code = $this->otp->issue($this->user);

    expect($this->otp->verify($this->user, $code->code))->toBeTrue()
        ->and($this->user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($code->fresh()->consumed_at)->not->toBeNull();
});

it('rejects a wrong code and counts the attempt', function (): void {
    $code = $this->otp->issue($this->user);

    expect($this->otp->verify($this->user, '000000'))->toBeFalse()
        ->and($code->fresh()->attempts)->toBe(1)
        ->and($this->user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('locks out after five wrong attempts', function (): void {
    $code = $this->otp->issue($this->user);

    foreach (range(1, OtpService::MAX_ATTEMPTS) as $ignored) {
        $this->otp->verify($this->user, '000000');
    }

    expect($this->otp->verify($this->user, $code->code))->toBeFalse()
        ->and($this->user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuses an expired code', function (): void {
    $code = $this->otp->issue($this->user);
    $code->update(['expires_at' => now()->subMinute()]);

    expect($this->otp->verify($this->user, $code->code))->toBeFalse();
});

it('invalidates the previous code when a new one is requested', function (): void {
    $first = $this->otp->issue($this->user);
    $second = $this->otp->issue($this->user);

    expect($first->fresh()->consumed_at)->not->toBeNull()
        ->and($this->otp->verify($this->user, $first->code))->toBeFalse()
        ->and($this->otp->verify($this->user, $second->code))->toBeTrue();
});
