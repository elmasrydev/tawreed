<?php

namespace App\Http\Requests\Supplier;

use App\Models\PortfolioItem;
use Illuminate\Foundation\Http\FormRequest;

class PortfolioItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A new entry needs at least one image; when editing, the images that are
     * kept plus the new ones must stay within the limit.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PortfolioItem|null $item */
        $item = $this->route('portfolioItem');
        $kept = $item ? $item->getMedia('images')->count() - count((array) $this->input('remove_images', [])) : 0;

        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'client_type' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.now()->year],
            'images' => [$item ? 'nullable' : 'required', 'array', 'max:'.max(0, PortfolioItem::MAX_IMAGES - $kept)],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('supplier.portfolio_title'),
            'description' => __('supplier.portfolio_description'),
            'client_type' => __('supplier.portfolio_client_type'),
            'year' => __('supplier.portfolio_year'),
            'images' => __('supplier.portfolio_images'),
            'images.*' => __('supplier.portfolio_images'),
        ];
    }
}
