<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Database\Factories\BusinessTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name_ar', 'name_en', 'sort', 'is_active'])]
class BusinessType extends Model
{
    /** @use HasFactory<BusinessTypeFactory> */
    use HasBilingualName, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function buyerProfiles(): HasMany
    {
        return $this->hasMany(BuyerProfile::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort');
    }
}
