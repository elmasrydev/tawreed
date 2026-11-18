<?php

namespace App\Actions\Subscription;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\PaymentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlePayment
{
    public function __construct(private SubscribeToPlan $subscribeToPlan) {}

    /**
     * Applies a verified gateway outcome to its payment. Safe to call more than
     * once for the same transaction: the webhook and the browser redirect both
     * report it, and only the first success starts a subscription. A payment
     * that failed can still be paid by a later retry on the same checkout.
     */
    public function handle(PaymentResult $result): ?Payment
    {
        return DB::transaction(function () use ($result): ?Payment {
            $payment = Payment::query()
                ->where('reference', $result->reference)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                Log::warning('Payment callback for an unknown reference', ['reference' => $result->reference]);

                return null;
            }

            if ($payment->isPaid() || $result->isPending) {
                return $payment;
            }

            if (! $result->isSuccessful) {
                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'provider_transaction_id' => $result->transactionId,
                    'failure_reason' => $result->message ?: 'declined',
                    'payload' => $result->payload,
                ]);

                return $payment;
            }

            if ($result->amountCents !== $payment->totalCents()) {
                Log::error('Payment amount mismatch', [
                    'payment_id' => $payment->id,
                    'expected' => $payment->totalCents(),
                    'received' => $result->amountCents,
                ]);

                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'provider_transaction_id' => $result->transactionId,
                    'failure_reason' => 'amount_mismatch',
                    'payload' => $result->payload,
                ]);

                return $payment;
            }

            $subscription = $this->subscribeToPlan->handle($payment, (string) $result->transactionId);

            $payment->update([
                'status' => PaymentStatus::Paid,
                'subscription_id' => $subscription->id,
                'provider_transaction_id' => $result->transactionId,
                'method' => $result->method,
                'failure_reason' => null,
                'payload' => $result->payload,
                'paid_at' => now(),
            ]);

            return $payment;
        });
    }
}
