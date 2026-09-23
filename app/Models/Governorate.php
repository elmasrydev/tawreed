<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Database\Factories\GovernorateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name_ar', 'name_en', 'sort'])]
class Governorate extends Model
{
    /** @use HasFactory<GovernorateFactory> */
    use HasBilingualName, HasFactory;

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }

    public function supplierProfiles(): BelongsToMany
    {
        return $this->belongsToMany(SupplierProfile::class, 'supplier_governorate');
    }
}
