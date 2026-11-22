<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Rules\DiffersFromLinkedAccountPasswords;
use App\Rules\EgyptianMobile;
use App\Rules\UniqueContactForRole;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class BuyerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The email and phone may already belong to this person's account under
     * the other role, as long as the password differs from it.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', new EgyptianMobile, new UniqueContactForRole('phone', UserRole::Buyer)],
            'email' => ['required', 'email', 'max:180', new UniqueContactForRole('email', UserRole::Buyer)],
            'password' => [
                'required', 'confirmed', Password::defaults(),
                new DiffersFromLinkedAccountPasswords($this->input('email'), $this->input('phone')),
            ],
            'company_name' => ['required', 'string', 'max:180'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'business_type_id' => ['required', 'exists:business_types,id'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'commercial_reg_no' => ['nullable', 'string', 'max:60'],
            'tax_card_no' => ['nullable', 'string', 'max:60'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNumber::normalize($this->string('phone')->toString())]);
        }
    }
}
