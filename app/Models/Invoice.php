<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'subscription_id', 'supplier_id', 'number',
    'amount_egp', 'vat_egp', 'total_egp', 'issued_at',
])]
class Invoice extends Model implements HasMedia
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Sequential per-year invoice number, e.g. INV-2026-00042.
     */
    public static function nextNumber(): string
    {
        $prefix = 'INV-'.now()->year.'-';

        $latest = self::where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $next = $latest ? ((int) substr($latest, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'amount_egp' => 'decimal:2',
            'vat_egp' => 'decimal:2',
            'total_egp' => 'decimal:2',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pdf')->singleFile();
    }
}
