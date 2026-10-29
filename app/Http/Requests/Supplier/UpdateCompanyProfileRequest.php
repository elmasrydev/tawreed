<?php

namespace App\Http\Requests\Supplier;

use App\Rules\EgyptianMobile;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The registration rules for the fields a supplier may change themselves.
     * The commercial registration and tax numbers are verified identifiers,
     * so only an admin changes them.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:180'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', new EgyptianMobile, Rule::unique('users', 'phone')->ignore($this->user())],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($this->user())],
            'facility_address' => ['nullable', 'string', 'max:255'],
            'activity_description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNumber::normalize($this->string('phone')->toString())]);
        }
    }
}
