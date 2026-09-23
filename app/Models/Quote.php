<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Enums\VatMode;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'rfq_id', 'supplier_id', 'unit_price', 'total_price', 'min_order_qty',
    'vat_mode', 'vat_amount', 'delivery_cost', 'expected_delivery_date',
    'validity_days', 'payment_terms', 'sample_availability', 'brand_origin',
    'extra_specs', 'warranty_policy', 'status',
])]
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'min_order_qty' => 'decimal:3',
            'vat_amount' => 'decimal:2',
            'delivery_cost' => 'decimal:2',
            'expected_delivery_date' => 'date',
            'selected_at' => 'datetime',
            'rejected_at' => 'datetime',
            'status' => QuoteStatus::class,
            'vat_mode' => VatMode::class,
        ];
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function isSelected(): bool
    {
        return $this->status === QuoteStatus::Selected;
    }

    public function isRejected(): bool
    {
        return $this->status === QuoteStatus::Rejected;
    }

    public function isShortlisted(): bool
    {
        return $this->status === QuoteStatus::Shortlisted;
    }

    /**
     * A quote closed automatically because the buyer awarded another one. The
     * status is still Rejected, but no reason was given, so it reads as "not
     * selected" rather than a judgement on the offer.
     */
    public function isNotSelected(): bool
    {
        return $this->isRejected() && $this->rejection_reason_id === null;
    }

    /**
     * The status wording shown to people, distinguishing "not selected" from a
     * reasoned rejection.
     */
    public function statusLabel(): string
    {
        return $this->isNotSelected()
            ? __('enums.quote_status.not_selected')
            : $this->status->label();
    }

    public function isActionable(): bool
    {
        return $this->status->isActionable();
    }

    public function hasExpired(): bool
    {
        return $this->created_at->addDays($this->validity_days)->isPast();
    }

    /**
     * The grand total a buyer compares across quotes: price plus VAT and delivery.
     */
    public function grandTotal(): float
    {
        $vat = $this->vat_mode === VatMode::Excluded ? (float) $this->vat_amount : 0.0;

        return (float) $this->total_price + $vat + (float) $this->delivery_cost;
    }

    #[Scope]
    protected function forSupplier(Builder $query, User $supplier): void
    {
        $query->where('supplier_id', $supplier->id);
    }

    #[Scope]
    protected function selected(Builder $query): void
    {
        $query->where('status', QuoteStatus::Selected);
    }
}
