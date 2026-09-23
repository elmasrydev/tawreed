<?php

namespace App\Actions\Quote;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\RejectionReason;

class RejectQuote
{
    public function handle(Quote $quote, RejectionReason $reason): Quote
    {
        $quote->forceFill([
            'status' => QuoteStatus::Rejected,
            'rejection_reason_id' => $reason->id,
            'rejected_at' => now(),
        ])->save();

        return $quote;
    }
}
