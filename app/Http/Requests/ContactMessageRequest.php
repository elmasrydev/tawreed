<?php

namespace App\Http\Requests;

use App\Rules\EgyptianMobile;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The "website" field is a honeypot: it is hidden from people, so only
     * bots fill it in.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', new EgyptianMobile],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * Failed validation returns to the form, not the top of the page.
     */
    protected function getRedirectUrl(): string
    {
        return route('splash').'#contact';
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => PhoneNumber::normalize($this->string('phone')->toString())]);
        }
    }
}
