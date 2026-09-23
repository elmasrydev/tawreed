<?php

namespace App\Models;

use App\Enums\RfqStatus;
use App\Enums\SupplyType;
use Database\Factories\RfqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'buyer_id', 'category_id', 'subcategory_id', 'unit_id', 'governorate_id',
    'title', 'specs', 'quantity', 'delivery_date', 'supply_type', 'recurrence_note',
    'quote_deadline', 'notes', 'status',
])]
class Rfq extends Model implements HasMedia
{
    /** @use HasFactory<RfqFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * A request is flagged "ending soon" this many days before its quote deadline.
     */
    public const ENDING_SOON_DAYS = 2;

    protected static function booted(): void
    {
        static::creating(function (self $rfq): void {
            $rfq->reference ??= self::nextReference();
        });
    }

    /**
     * Sequential per-year reference shown to both sides, e.g. RFQ-2026-00042.
     */
    public static function nextReference(): string
    {
        $prefix = 'RFQ-'.now()->year.'-';

        $latest = self::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $next = $latest ? ((int) substr($latest, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'quote_deadline' => 'date',
            'published_at' => 'datetime',
            'awarded_at' => 'datetime',
            'quantity' => 'decimal:3',
            'quotes_count' => 'integer',
            'best_price' => 'decimal:2',
            'status' => RfqStatus::class,
            'supply_type' => SupplyType::class,
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function awardedQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'awarded_quote_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isOpen(): bool
    {
        return $this->status === RfqStatus::Open;
    }

    /**
     * An unpublished request the buyer can still resume in the wizard. Drafts
     * may be incomplete, so every field other than the title can be null.
     */
    public function isDraft(): bool
    {
        return $this->status === RfqStatus::Draft;
    }

    public function acceptsQuotes(): bool
    {
        return $this->status->acceptsQuotes()
            && $this->quote_deadline !== null
            && ! $this->quote_deadline->isPast();
    }

    public function isEndingSoon(): bool
    {
        return $this->isOpen()
            && $this->quote_deadline?->isFuture() === true
            && now()->diffInDays($this->quote_deadline, absolute: true) <= self::ENDING_SOON_DAYS;
    }

    /**
     * The quantity without trailing zeros, e.g. "500" or "2.5", or null while a
     * draft has none yet.
     */
    public function formattedQuantity(): ?string
    {
        if ($this->quantity === null) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $this->quantity, 3), '0'), '.');
    }

    /**
     * "Category › Sub-category", or null while a draft has no category yet.
     */
    public function categoryPath(): ?string
    {
        if ($this->category === null) {
            return null;
        }

        return collect([$this->category->name, $this->subcategory?->name])->filter()->implode(' › ');
    }

    /**
     * What a supplier is allowed to see about the buyer while the request is open.
     */
    public function anonymousBuyerLabel(): string
    {
        return $this->buyer->buyerProfile?->anonymousLabel() ?? '';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(600)->height(600)->nonQueued();
    }

    #[Scope]
    protected function browsable(Builder $query): void
    {
        $query->whereIn('status', RfqStatus::browsable())
            ->whereDate('quote_deadline', '>=', now()->toDateString());
    }

    #[Scope]
    protected function forBuyer(Builder $query, User $buyer): void
    {
        $query->where('buyer_id', $buyer->id);
    }
}
