<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Issues and checks the one-time codes that confirm an email address at
 * registration.
 */
class OtpService
{
    public const CODE_LENGTH = 6;

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * Whether new accounts must confirm their email with a code (OTP_ENABLED).
     */
    public function isEnabled(): bool
    {
        return (bool) config('auth.otp.enabled');
    }

    /**
     * Starts email verification for an account: sends a code, or marks the
     * email verified straight away when codes are switched off.
     */
    public function startVerification(User $user): void
    {
        if ($this->isEnabled()) {
            $this->issue($user);

            return;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }

    /**
     * Invalidates any outstanding code and emails a fresh one. The email goes
     * out after the transaction commits, so a code is never sent that was
     * rolled back. A mail outage is reported rather than thrown, so sign-up
     * still completes and the user can ask for the code again.
     */
    public function issue(User $user): OtpCode
    {
        $code = DB::transaction(function () use ($user): OtpCode {
            $user->otpCodes()->whereNull('consumed_at')->update(['consumed_at' => now()]);

            return OtpCode::create([
                'user_id' => $user->id,
                'code' => $this->generateCode(),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);
        });

        try {
            $user->notify(new VerificationCodeNotification($code));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $code;
    }

    /**
     * Marks the email as verified when the code matches an unexpired,
     * unconsumed code that has not been guessed at too many times.
     */
    public function verify(User $user, string $code): bool
    {
        $otp = $user->otpCodes()
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->latest('id')
            ->first();

        if ($otp === null) {
            return false;
        }

        if (! hash_equals($otp->code, $code)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return true;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
