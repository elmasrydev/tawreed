<?php

namespace App\Http\Requests\Auth;

use App\Rules\EgyptianMobile;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SupplierRegistrationRequest extends FormRequest
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
            'phone' => ['required', 'string', new EgyptianMobile, 'unique:users,phone'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company_name' => ['required', 'string', 'max:180'],
            'commercial_reg_no' => ['required', 'string', 'max:60'],
            'tax_number' => ['required', 'string', 'max:60'],
            'facility_address' => ['nullable', 'string', 'max:255'],
            'activity_description' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'max:120'],
            'governorate_ids' => ['required', 'array', 'min:1'],
            'governorate_ids.*' => ['exists:governorates,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'documents.commercial_registration' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.tax_card' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNumber::normalize($this->string('phone')->toString())]);
        }
    }
}
