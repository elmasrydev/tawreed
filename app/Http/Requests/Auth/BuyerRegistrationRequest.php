<?php

namespace App\Http\Requests\Auth;

use App\Rules\EgyptianMobile;
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', new EgyptianMobile, 'unique:users,phone'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
