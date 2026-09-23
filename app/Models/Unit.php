<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name_ar', 'name_en', 'sort'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasBilingualName, HasFactory;

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }
}
