<?php

namespace App\Rules;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An email or phone may be shared by one buyer and one supplier account, but
 * never by two accounts of the same role. Admin contact details stay unique
 * across every account so the admin panel sign-in is never ambiguous.
 */
class UniqueContactForRole implements ValidationRule
{
    /**
     * @param  'email'|'phone'  $column
     */
    public function __construct(
        private string $column,
        private ?UserRole $role,
        private ?int $ignoreUserId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $value = $this->column === 'phone' ? PhoneNumber::normalize($value) : $value;

        $taken = User::withTrashed()
            ->where($this->column, $value)
            ->when($this->ignoreUserId, fn ($query) => $query->whereKeyNot($this->ignoreUserId))
            ->when($this->role !== UserRole::Admin, fn ($query) => $query->whereIn('role', array_filter([$this->role, UserRole::Admin])))
            ->exists();

        if ($taken) {
            $fail('validation.unique')->translate(['attribute' => __('ui.'.$this->column)]);
        }
    }
}
