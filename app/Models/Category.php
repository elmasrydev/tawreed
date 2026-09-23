<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['parent_id', 'slug', 'name_ar', 'name_en', 'image_path', 'sort', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasBilingualName, HasFactory;

    /**
     * Disk that holds the category photos uploaded from the admin panel.
     */
    public const IMAGE_DISK = 'public';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }

    /**
     * Public URL of the admin-uploaded photo shown on the landing page, if any.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path
            ? Storage::disk(self::IMAGE_DISK)->url($this->image_path)
            : null);
    }

    public function isMain(): bool
    {
        return $this->parent_id === null;
    }

    #[Scope]
    protected function main(Builder $query): void
    {
        $query->whereNull('parent_id')->where('is_active', true)->orderBy('sort');
    }
}
