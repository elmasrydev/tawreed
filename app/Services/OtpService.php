<?php

namespace App\Services;

use App\Contracts\SmsSender;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Issues and checks the one-time codes that confirm a phone number at
 * registration.
 */
class OtpService
{
    public const CODE_LENGTH = 6;

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private SmsSender $sms) {}

    /**
     * Invalidates any outstanding code and sends a fresh one.
     */
    public function issue(User $user): OtpCode
    {
        return DB::transaction(function () use ($user): OtpCode {
            $user->otpCodes()->whereNull('consumed_at')->update(['consumed_at' => now()]);

            $code = OtpCode::create([
                'user_id' => $user->id,
                'code' => $this->generateCode(),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);

            $this->sms->send($user->phone, __('otp.message', ['code' => $code->code]));

            return $code;
        });
    }

    /**
     * Marks the phone as verified when the code matches an unexpired,
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
        $user->forceFill(['phone_verified_at' => now()])->save();

        return true;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
