<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    /**
     * A quote is visible to the supplier who wrote it, the buyer who owns the
     * request, and admins. Competing suppliers never see it.
     */
    public function view(User $user, Quote $quote): bool
    {
        return $user->id === $quote->supplier_id
            || $user->id === $quote->rfq->buyer_id
            || $user->isAdmin();
    }

    /**
     * Only the buyer who owns the request may shortlist, reject or select.
     */
    public function decide(User $user, Quote $quote): bool
    {
        return $user->id === $quote->rfq->buyer_id
            && $quote->isActionable()
            && $quote->rfq->isOpen();
    }

    public function withdraw(User $user, Quote $quote): bool
    {
        return $user->id === $quote->supplier_id && $quote->isActionable();
    }
}
