<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The registration rules for the supplier's coverage areas, supplied
     * categories and accepted payment method.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'governorate_ids' => ['required', 'array', 'min:1'],
            'governorate_ids.*' => ['integer', 'exists:governorates,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'payment_method' => ['nullable', 'string', 'max:120'],
        ];
    }
}
