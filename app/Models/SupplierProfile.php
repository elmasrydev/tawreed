<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Database\Factories\SupplierProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'user_id', 'company_name', 'commercial_reg_no', 'tax_number', 'facility_address',
    'activity_description', 'payment_method', 'years_active',
    'verification_status', 'verification_note',
])]
class SupplierProfile extends Model implements HasMedia
{
    /** @use HasFactory<SupplierProfileFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function governorates(): BelongsToMany
    {
        return $this->belongsToMany(Governorate::class, 'supplier_governorate');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Verified;
    }

    public function canSubmitQuotes(): bool
    {
        return $this->verification_status->allowsQuoting();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('portfolio');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->height(400)->nonQueued();
    }

    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->where('verification_status', VerificationStatus::Verified);
    }

    #[Scope]
    protected function awaitingReview(Builder $query): void
    {
        $query->where('verification_status', VerificationStatus::Pending);
    }
}
