<?php

namespace App\Actions\Quote;

use App\Enums\QuoteStatus;
use App\Models\Quote;

class ShortlistQuote
{
    /**
     * Shortlisting is a toggle: a listed quote returns to pending.
     */
    public function handle(Quote $quote): Quote
    {
        $quote->forceFill([
            'status' => $quote->isShortlisted() ? QuoteStatus::Pending : QuoteStatus::Shortlisted,
        ])->save();

        return $quote;
    }
}
