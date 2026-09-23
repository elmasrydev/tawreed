<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Reference tables keep both languages in columns rather than translation files,
 * because admins add categories and units at runtime.
 */
trait HasBilingualName
{
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => app()->isLocale('ar') ? $this->name_ar : $this->name_en);
    }
}
