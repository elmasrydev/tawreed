<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Marks checkouts the supplier abandoned as expired so the admin log stays
 * readable. A late success from the gateway still settles an expired payment.
 */
#[Signature('payments:expire-stale {--minutes=60 : Age after which a pending payment is expired}')]
#[Description('Expire pending payments that were never completed')]
class ExpireStalePayments extends Command
{
    public function handle(): int
    {
        $expired = Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->where('created_at', '<', now()->subMinutes((int) $this->option('minutes')))
            ->update(['status' => PaymentStatus::Expired]);

        $this->info("Expired {$expired} stale payment(s).");

        return self::SUCCESS;
    }
}
