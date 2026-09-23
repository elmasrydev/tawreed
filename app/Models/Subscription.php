<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'supplier_id', 'plan_id', 'starts_at', 'ends_at', 'status',
    'auto_renew', 'amount_egp', 'payment_ref',
])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /**
     * Suppliers are warned this many days before their plan lapses.
     */
    public const EXPIRY_WARNING_DAYS = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'amount_egp' => 'decimal:2',
            'status' => SubscriptionStatus::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isCurrent(): bool
    {
        return $this->status->unlocksPlatform() && $this->ends_at->isFuture();
    }

    public function isExpiringSoon(): bool
    {
        return $this->isCurrent()
            && $this->daysRemaining() <= self::EXPIRY_WARNING_DAYS;
    }

    public function daysRemaining(): int
    {
        return max(0, (int) ceil(now()->floatDiffInDays($this->ends_at, false)));
    }

    #[Scope]
    protected function current(Builder $query): void
    {
        $query->whereIn('status', SubscriptionStatus::unlockingValues())
            ->where('ends_at', '>', now());
    }

    #[Scope]
    protected function expiringWithin(Builder $query, int $days): void
    {
        $query->whereIn('status', SubscriptionStatus::unlockingValues())
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }
}
