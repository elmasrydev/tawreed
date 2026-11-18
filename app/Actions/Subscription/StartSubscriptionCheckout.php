<?php

namespace App\Actions\Subscription;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;

class StartSubscriptionCheckout
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Records a pending payment for the plan, VAT included, and returns the
     * gateway URL where the supplier completes it.
     *
     * @throws PaymentFailedException
     */
    public function handle(User $supplier, Plan $plan): string
    {
        $net = (float) $plan->price_egp;
        $vat = round($net * SubscribeToPlan::VAT_RATE, 2);

        $payment = Payment::create([
            'supplier_id' => $supplier->id,
            'plan_id' => $plan->id,
            'gateway' => $this->gateway->name(),
            'reference' => (string) Str::uuid(),
            'amount_egp' => $net,
            'vat_egp' => $vat,
            'total_egp' => $net + $vat,
            'status' => PaymentStatus::Pending,
        ]);

        try {
            return $this->gateway->createCheckout($payment);
        } catch (PaymentFailedException $exception) {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'failure_reason' => 'checkout_unavailable',
            ]);

            throw $exception;
        }
    }
}
