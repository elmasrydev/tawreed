<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Database\Factories\RejectionReasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name_ar', 'name_en', 'sort'])]
class RejectionReason extends Model
{
    /** @use HasFactory<RejectionReasonFactory> */
    use HasBilingualName, HasFactory;

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
