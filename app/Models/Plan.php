<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'name_ar', 'name_en', 'description_ar', 'description_en',
    'days', 'price_egp', 'trial_days', 'is_best_value', 'is_active', 'sort',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasBilingualName, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_egp' => 'decimal:2',
            'is_best_value' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    protected function description(): Attribute
    {
        return Attribute::get(fn (): ?string => app()->isLocale('ar') ? $this->description_ar : $this->description_en);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort');
    }
}
