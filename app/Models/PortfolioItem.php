<?php

namespace App\Models;

use Database\Factories\PortfolioItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A past project a supplier showcases on their public profile.
 */
#[Fillable(['supplier_profile_id', 'title', 'description', 'client_type', 'year'])]
class PortfolioItem extends Model implements HasMedia
{
    /** @use HasFactory<PortfolioItemFactory> */
    use HasFactory, InteractsWithMedia;

    public const MAX_IMAGES = 6;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    public function supplierProfile(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(600)->height(450)->nonQueued();
    }
}
