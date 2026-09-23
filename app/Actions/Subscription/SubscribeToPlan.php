<?php

namespace App\Actions\Subscription;

use App\Contracts\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscribeToPlan
{
    /**
     * Egyptian VAT applied to the subscription fee.
     */
    public const VAT_RATE = 0.14;

    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Charges the supplier, starts the plan and issues an electronic invoice.
     * A plan bought while one is still running extends from its end date
     * rather than discarding the remaining days.
     */
    public function handle(User $supplier, Plan $plan): Subscription
    {
        $reference = $this->gateway->charge($supplier, $plan);

        return DB::transaction(function () use ($supplier, $plan, $reference): Subscription {
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
                'amount_egp' => $plan->price_egp,
                'payment_ref' => $reference,
            ]);

            $this->issueInvoice($subscription);

            return $subscription;
        });
    }

    private function issueInvoice(Subscription $subscription): Invoice
    {
        $net = (float) $subscription->amount_egp;
        $vat = round($net * self::VAT_RATE, 2);

        return Invoice::create([
            'subscription_id' => $subscription->id,
            'supplier_id' => $subscription->supplier_id,
            'number' => Invoice::nextNumber(),
            'amount_egp' => $net,
            'vat_egp' => $vat,
            'total_egp' => $net + $vat,
            'issued_at' => now(),
        ]);
    }
}
