<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Members sign in with either their email or their phone number.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = $this->matchingAccount();

        if ($user === null) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());

        // Only someone who knows the password learns the account is suspended.
        if ($user->isSuspended()) {
            throw ValidationException::withMessages(['login' => __('auth.suspended')]);
        }

        Auth::login($user, $this->boolean('remember'));
    }

    /**
     * A buyer and a supplier account may share an email or phone, each with
     * its own password, so the password decides which account signs in.
     */
    protected function matchingAccount(): ?User
    {
        $login = $this->string('login')->toString();
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $password = $this->string('password')->toString();

        return User::query()
            ->where($field, $field === 'phone' ? PhoneNumber::normalize($login) : $login)
            ->orderBy('id')
            ->get()
            ->first(fn (User $user): bool => Hash::check($password, $user->password));
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')->toString()).'|'.$this->ip());
    }
}
