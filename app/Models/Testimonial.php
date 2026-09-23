<?php

namespace App\Models;

use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A quote from a real platform member, added by an admin for the landing page.
 * Text columns are stored in both languages, like the reference tables.
 */
#[Fillable([
    'author_name', 'author_role_ar', 'author_role_en', 'location_ar', 'location_en',
    'quote_ar', 'quote_en', 'rating', 'is_published', 'sort',
])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected function quote(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->localized('quote'));
    }

    protected function authorRole(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->localized('author_role'));
    }

    protected function location(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->localized('location'));
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort')->orderBy('id');
    }

    /**
     * The value in the current language, falling back to the other one when an
     * admin filled in only one side.
     */
    private function localized(string $column): ?string
    {
        $locale = app()->isLocale('ar') ? 'ar' : 'en';
        $fallback = $locale === 'ar' ? 'en' : 'ar';

        return $this->{"{$column}_{$locale}"} ?: $this->{"{$column}_{$fallback}"};
    }
}
