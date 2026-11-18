<?php

namespace App\Services\Payment;

/**
 * A verified transaction outcome reported by a payment gateway.
 */
final readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $reference,
        public ?string $transactionId,
        public bool $isSuccessful,
        public bool $isPending,
        public int $amountCents,
        public ?string $method = null,
        public ?string $message = null,
        public array $payload = [],
    ) {}
}
