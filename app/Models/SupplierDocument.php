<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\SupplierDocumentType;
use Database\Factories\SupplierDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['supplier_profile_id', 'type', 'status', 'note'])]
class SupplierDocument extends Model implements HasMedia
{
    /** @use HasFactory<SupplierDocumentFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SupplierDocumentType::class,
            'status' => DocumentStatus::class,
        ];
    }

    public function supplierProfile(): BelongsTo
    {
        return $this->belongsTo(SupplierProfile::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }
}
