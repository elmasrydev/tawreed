<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuyerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The registration rules for the fields a buyer may change themselves.
     * The phone and email are sign-in credentials, so only an admin
     * changes them.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'company_name' => ['required', 'string', 'max:180'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'business_type_id' => ['required', 'exists:business_types,id'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'commercial_reg_no' => ['nullable', 'string', 'max:60'],
            'tax_card_no' => ['nullable', 'string', 'max:60'],
        ];
    }
}
