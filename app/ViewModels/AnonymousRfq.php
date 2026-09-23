<?php

namespace App\ViewModels;

use App\Enums\RfqStatus;
use App\Enums\SupplyType;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * The supplier-facing projection of a request.
 *
 * Suppliers must never see who posted a request, nor what competitors offered.
 * Templates receive this object instead of the Eloquent model so buyer identity
 * cannot leak by accident through a relationship.
 */
class AnonymousRfq
{
    /**
     * Relations to eager load before projecting a list of requests. The
     * supplier's own quote is loaded separately, constrained to them.
     *
     * @var list<string>
     */
    public const RELATIONS = ['buyer.buyerProfile.businessType', 'unit', 'governorate', 'category', 'subcategory'];

    private function __construct(
        public readonly int $id,
        public readonly string $reference,
        public readonly string $title,
        public readonly string $specs,
        public readonly string $quantity,
        public readonly string $unit,
        public readonly string $category,
        public readonly ?string $subcategory,
        public readonly string $governorate,
        public readonly string $buyerEntityType,
        public readonly string $deliveryDate,
        public readonly string $quoteDeadline,
        public readonly ?CarbonInterface $deadlineAt,
        public readonly string $supplyType,
        public readonly bool $isRecurring,
        public readonly ?string $recurrenceNote,
        public readonly ?string $notes,
        public readonly int $quotesCount,
        public readonly bool $isEndingSoon,
        public readonly bool $acceptsQuotes,
        public readonly ?RfqStatus $status,
        public readonly ?Quote $ownQuote,
    ) {}

    /**
     * Pass the supplier to resolve their own quote. The request's `quotes`
     * relation may be constrained to that supplier to avoid loading rivals.
     */
    public static function make(Rfq $rfq, ?User $supplier = null): self
    {
        $profile = $rfq->buyer?->buyerProfile;

        return new self(
            id: $rfq->id,
            reference: (string) $rfq->reference,
            title: (string) $rfq->title,
            specs: (string) $rfq->specs,
            quantity: $rfq->quantity !== null ? rtrim(rtrim(number_format((float) $rfq->quantity, 3), '0'), '.') : '',
            unit: $rfq->unit?->name ?? '',
            category: $rfq->category?->name ?? '',
            subcategory: $rfq->subcategory?->name,
            governorate: $rfq->governorate?->name ?? '',
            buyerEntityType: $profile?->businessType?->name ?? '',
            deliveryDate: $rfq->delivery_date?->translatedFormat('j M Y') ?? '—',
            quoteDeadline: $rfq->quote_deadline?->translatedFormat('j M Y') ?? '—',
            deadlineAt: $rfq->quote_deadline,
            supplyType: $rfq->supply_type?->label() ?? '',
            isRecurring: $rfq->supply_type === SupplyType::Recurring,
            recurrenceNote: $rfq->recurrence_note,
            notes: $rfq->notes,
            quotesCount: (int) ($rfq->quotes_count ?? 0),
            isEndingSoon: $rfq->quote_deadline !== null && $rfq->isEndingSoon(),
            acceptsQuotes: $rfq->quote_deadline !== null && $rfq->acceptsQuotes(),
            status: $rfq->status,
            ownQuote: $supplier
                ? $rfq->quotes->firstWhere('supplier_id', $supplier->id)
                : null,
        );
    }

    /**
     * The only description of the buyer a supplier ever sees before selection.
     */
    public function buyerLabel(): string
    {
        return trim("{$this->buyerEntityType} — {$this->governorate}", ' —');
    }

    /**
     * The buyer's entity type, or a neutral stand-in when it is unknown.
     */
    public function buyerType(): string
    {
        return $this->buyerEntityType !== '' ? $this->buyerEntityType : __('supplier.anonymous_buyer');
    }

    /**
     * "Category › Subcategory", as the design shows it on request cards.
     */
    public function categoryPath(): string
    {
        return collect([$this->category, $this->subcategory])->filter()->implode(' › ');
    }

    /**
     * "1,200 kg" style quantity with its unit.
     */
    public function quantityWithUnit(): string
    {
        return trim("{$this->quantity} {$this->unit}");
    }

    public function hasOwnQuote(): bool
    {
        return $this->ownQuote !== null;
    }
}
