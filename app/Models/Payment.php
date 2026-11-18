<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One attempt to pay for a plan. A subscription only starts once the
 * gateway confirms the payment, so failed and abandoned attempts stay here.
 */
#[Fillable([
    'supplier_id', 'plan_id', 'subscription_id', 'gateway', 'reference',
    'provider_intention_id', 'provider_transaction_id', 'amount_egp', 'vat_egp',
    'total_egp', 'status', 'method', 'failure_reason', 'payload', 'paid_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_egp' => 'decimal:2',
            'vat_egp' => 'decimal:2',
            'total_egp' => 'decimal:2',
            'status' => PaymentStatus::class,
            'payload' => 'array',
            'paid_at' => 'datetime',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * The total in piasters, the unit payment providers charge in.
     */
    public function totalCents(): int
    {
        return (int) round((float) $this->total_egp * 100);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }
}
