<?php

namespace App\Rules;

use App\Models\User;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/**
 * Members sign in with an email or phone that a buyer and a supplier account
 * may share, so the password is what tells the two apart. A new password may
 * therefore never match the other account's.
 */
class DiffersFromLinkedAccountPasswords implements ValidationRule
{
    public function __construct(
        private ?string $email,
        private ?string $phone,
        private ?int $ignoreUserId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $matchesLinkedAccount = User::linkedTo($this->email, $this->phone ? PhoneNumber::normalize($this->phone) : null)
            ->when($this->ignoreUserId, fn ($query) => $query->whereKeyNot($this->ignoreUserId))
            ->get(['id', 'password'])
            ->contains(fn (User $user): bool => Hash::check($value, $user->password));

        if ($matchesLinkedAccount) {
            $fail('auth.password_matches_linked_account')->translate();
        }
    }
}
