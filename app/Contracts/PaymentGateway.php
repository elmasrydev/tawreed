<?php

namespace App\Contracts;

use App\Exceptions\InvalidPaymentSignatureException;
use App\Exceptions\PaymentFailedException;
use App\Models\Payment;
use App\Services\Payment\PaymentResult;

/**
 * A hosted-checkout payment provider. The supplier is sent to the provider's
 * page, and the outcome comes back later as a signed callback.
 */
interface PaymentGateway
{
    /**
     * Short name stored on each payment, e.g. "paymob".
     */
    public function name(): string;

    /**
     * Opens a checkout for the payment and returns the URL to send the supplier to.
     *
     * @throws PaymentFailedException
     */
    public function createCheckout(Payment $payment): string;

    /**
     * Verifies a callback's signature and reads its outcome.
     *
     * @param  array<string, mixed>  $data  Flat, dot-keyed callback fields including the signature.
     *
     * @throws InvalidPaymentSignatureException
     */
    public function verifyCallback(array $data): PaymentResult;
}
