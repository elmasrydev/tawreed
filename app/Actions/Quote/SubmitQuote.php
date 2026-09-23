<?php

namespace App\Actions\Quote;

use App\Enums\QuoteStatus;
use App\Enums\ReportType;
use App\Models\Quote;
use App\Models\Report;
use App\Models\Rfq;
use App\Models\User;
use App\Support\ContactDetailDetector;
use Illuminate\Support\Facades\DB;

class SubmitQuote
{
    public function __construct(private ContactDetailDetector $detector) {}

    /**
     * Stores a supplier's quote and refreshes the request's cached counters.
     * Free-text fields carrying contact details are flagged for moderation
     * rather than silently accepted.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Rfq $rfq, User $supplier, array $data): Quote
    {
        return DB::transaction(function () use ($rfq, $supplier, $data): Quote {
            $quote = Quote::create([
                ...$data,
                'rfq_id' => $rfq->id,
                'supplier_id' => $supplier->id,
                'status' => QuoteStatus::Pending,
            ]);

            $this->refreshRfqTotals($rfq);
            $this->flagContactDetails($quote, $supplier, $data);

            return $quote;
        });
    }

    /**
     * Keeps `quotes_count` and `best_price` in step so the buyer's list and the
     * supplier feed do not need to aggregate on every read.
     */
    private function refreshRfqTotals(Rfq $rfq): void
    {
        $rfq->forceFill([
            'quotes_count' => $rfq->quotes()->count(),
            'best_price' => $rfq->quotes()->min('total_price'),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function flagContactDetails(Quote $quote, User $supplier, array $data): void
    {
        $offenders = $this->detector->offendingFields([
            'extra_specs' => $data['extra_specs'] ?? null,
            'warranty_policy' => $data['warranty_policy'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'sample_availability' => $data['sample_availability'] ?? null,
            'brand_origin' => $data['brand_origin'] ?? null,
        ]);

        if ($offenders === []) {
            return;
        }

        Report::create([
            'reporter_id' => null,
            'reportable_type' => Quote::class,
            'reportable_id' => $quote->id,
            'type' => ReportType::ContactDetailsBypass,
            'reason' => __('quote.contact_details_flagged', [
                'fields' => implode(', ', $offenders),
                'supplier' => $supplier->supplierProfile?->company_name ?? $supplier->name,
            ]),
        ]);
    }
}
