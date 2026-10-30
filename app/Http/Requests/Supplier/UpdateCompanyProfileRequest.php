<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The registration rules for the fields a supplier may change themselves.
     * The phone and email are sign-in credentials, and the commercial
     * registration and tax numbers are verified identifiers, so only an
     * admin changes them.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:180'],
            'name' => ['required', 'string', 'max:120'],
            'facility_address' => ['nullable', 'string', 'max:255'],
            'activity_description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
