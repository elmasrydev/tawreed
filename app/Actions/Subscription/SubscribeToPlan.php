<?php

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscribeToPlan
{
    /**
     * Egyptian VAT applied to the subscription fee.
     */
    public const VAT_RATE = 0.14;

    /**
     * Starts the plan a confirmed payment bought and issues an electronic
     * invoice for exactly what was charged. A plan bought while one is still
     * running extends from its end date rather than discarding the remaining days.
     */
    public function handle(Payment $payment, string $paymentReference): Subscription
    {
        return DB::transaction(function () use ($payment, $paymentReference): Subscription {
            $supplier = $payment->supplier;
            $plan = $payment->plan;

            $current = $supplier->activeSubscription();
            $startsAt = $current?->ends_at ?? now();

            $isFirstEver = ! $supplier->subscriptions()->exists();
            $trialDays = $isFirstEver ? $plan->trial_days : 0;

            $subscription = Subscription::create([
                'supplier_id' => $supplier->id,
                'plan_id' => $plan->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addDays($plan->days + $trialDays),
                'status' => SubscriptionStatus::Active,
                'auto_renew' => false,
                'amount_egp' => $payment->amount_egp,
                'payment_ref' => $paymentReference,
            ]);

            Invoice::create([
                'subscription_id' => $subscription->id,
                'supplier_id' => $supplier->id,
                'number' => Invoice::nextNumber(),
                'amount_egp' => $payment->amount_egp,
                'vat_egp' => $payment->vat_egp,
                'total_egp' => $payment->total_egp,
                'issued_at' => now(),
            ]);

            return $subscription;
        });
    }
}
