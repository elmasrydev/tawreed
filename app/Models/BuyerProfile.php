<?php

namespace App\Models;

use Database\Factories\BuyerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'business_type_id', 'governorate_id', 'company_name',
    'company_address', 'job_title', 'commercial_reg_no', 'tax_card_no',
])]
class BuyerProfile extends Model
{
    /** @use HasFactory<BuyerProfileFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /**
     * The only identity a supplier may see before the buyer selects their quote:
     * business type and governorate, never the company or contact details.
     */
    public function anonymousLabel(): string
    {
        return "{$this->businessType->name} — {$this->governorate->name}";
    }
}
