<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Exceptions\PaymentFailedException;
use App\Models\Payment;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Local and test stand-in for Paymob. Checkout skips the provider and sends
 * the supplier straight back with a signed success, so the flow works end to
 * end without an account. It refuses to run in production.
 */
class FakePaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function createCheckout(Payment $payment): string
    {
        if (app()->isProduction()) {
            throw new PaymentFailedException('The fake payment gateway is disabled in production.');
        }

        return URL::signedRoute('supplier.subscription.payment-return', [
            'merchant_order_id' => $payment->reference,
            'id' => 'DEMO-'.Str::upper(Str::random(10)),
            'success' => 'true',
            'pending' => 'false',
            'amount_cents' => $payment->totalCents(),
        ]);
    }

    public function verifyCallback(array $data): PaymentResult
    {
        if (app()->isProduction() || ! request()->hasValidSignature()) {
            throw new InvalidPaymentSignatureException('Invalid fake gateway callback.');
        }

        return new PaymentResult(
            reference: (string) ($data['merchant_order_id'] ?? ''),
            transactionId: $data['id'] ?? null,
            isSuccessful: ($data['success'] ?? null) === 'true',
            isPending: ($data['pending'] ?? null) === 'true',
            amountCents: (int) ($data['amount_cents'] ?? 0),
            method: 'demo',
        );
    }
}
