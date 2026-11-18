<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Exceptions\PaymentFailedException;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Paymob Unified Checkout (Intention API). The supplier pays on Paymob's
 * hosted page; the signed transaction callback is the source of truth.
 */
class PaymobGateway implements PaymentGateway
{
    /**
     * Fields Paymob signs, in the order they are concatenated for the HMAC.
     */
    private const HMAC_FIELDS = [
        'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
        'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded',
        'is_standalone_payment', 'is_voided', 'order.id', 'owner', 'pending',
        'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
    ];

    /**
     * @param  array<int, int>  $integrationIds
     */
    public function __construct(
        private string $baseUrl,
        private string $secretKey,
        private string $publicKey,
        private string $hmacSecret,
        private array $integrationIds,
    ) {}

    public function name(): string
    {
        return 'paymob';
    }

    public function createCheckout(Payment $payment): string
    {
        if (! $this->isConfigured()) {
            Log::error('Paymob is not configured: set PAYMOB_SECRET_KEY, PAYMOB_PUBLIC_KEY, PAYMOB_HMAC_SECRET and PAYMOB_INTEGRATION_IDS.');

            throw new PaymentFailedException('Paymob is not configured.');
        }

        $payment->loadMissing(['plan', 'supplier']);

        $amountCents = $payment->totalCents();

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withToken($this->secretKey, 'Token')
                ->acceptJson()
                ->timeout(20)
                ->post('/v1/intention/', [
                    'amount' => $amountCents,
                    'currency' => 'EGP',
                    'payment_methods' => $this->integrationIds,
                    'items' => [[
                        'name' => Str::limit($payment->plan->name_en, 50, ''),
                        'amount' => $amountCents,
                        'description' => Str::limit("Tawreed subscription — {$payment->plan->days} days", 255, ''),
                        'quantity' => 1,
                    ]],
                    'billing_data' => $this->billingData($payment),
                    'special_reference' => $payment->reference,
                    'notification_url' => route('webhooks.paymob'),
                    'redirection_url' => route('supplier.subscription.payment-return'),
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Paymob intention failed', [
                'payment_id' => $payment->id,
                'status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'body' => $exception instanceof RequestException ? $exception->response->json() : null,
            ]);

            throw new PaymentFailedException('Could not open a Paymob checkout.', previous: $exception);
        }

        $clientSecret = $response->json('client_secret');

        if (! is_string($clientSecret) || $clientSecret === '') {
            throw new PaymentFailedException('Paymob returned no client secret.');
        }

        $payment->update(['provider_intention_id' => $response->json('id')]);

        return rtrim($this->baseUrl, '/').'/unifiedcheckout/?'.http_build_query([
            'publicKey' => $this->publicKey,
            'clientSecret' => $clientSecret,
        ]);
    }

    /**
     * Accepts both the server-to-server callback (flattened JSON, where the
     * order id is "order.id") and the browser redirect (query string, where
     * it is "order").
     */
    public function verifyCallback(array $data): PaymentResult
    {
        if ($this->hmacSecret === '') {
            throw new InvalidPaymentSignatureException('Paymob HMAC secret is not configured.');
        }

        if (! array_key_exists('order.id', $data) && array_key_exists('order', $data)) {
            $data['order.id'] = $data['order'];
        }

        $signature = $data['hmac'] ?? null;

        if (! is_string($signature) || $signature === '' || ! hash_equals($this->sign($data), strtolower($signature))) {
            throw new InvalidPaymentSignatureException('Paymob callback signature mismatch.');
        }

        $reference = $data['order.merchant_order_id'] ?? $data['merchant_order_id'] ?? $data['special_reference'] ?? null;

        if (! is_string($reference) || $reference === '') {
            throw new InvalidPaymentSignatureException('Paymob callback carries no merchant reference.');
        }

        $isSuccessful = $this->isTrue($data['success'] ?? false)
            && ! $this->isTrue($data['is_voided'] ?? false)
            && ! $this->isTrue($data['is_refunded'] ?? false);

        unset($data['hmac']);

        return new PaymentResult(
            reference: $reference,
            transactionId: isset($data['id']) ? (string) $data['id'] : null,
            isSuccessful: $isSuccessful,
            isPending: $this->isTrue($data['pending'] ?? false),
            amountCents: (int) ($data['amount_cents'] ?? 0),
            method: isset($data['source_data.sub_type']) ? (string) $data['source_data.sub_type'] : null,
            message: isset($data['data.message']) ? (string) $data['data.message'] : null,
            payload: $data,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sign(array $data): string
    {
        $concatenated = collect(self::HMAC_FIELDS)
            ->map(fn (string $field): string => $this->stringify($data[$field] ?? ''))
            ->implode('');

        return hash_hmac('sha512', $concatenated, $this->hmacSecret);
    }

    private function isConfigured(): bool
    {
        return $this->secretKey !== ''
            && $this->publicKey !== ''
            && $this->hmacSecret !== ''
            && $this->integrationIds !== [];
    }

    /**
     * @return array<string, string>
     */
    private function billingData(Payment $payment): array
    {
        $supplier = $payment->supplier;
        $names = preg_split('/\s+/', trim((string) $supplier->name), 2) ?: [];

        return [
            'first_name' => $names[0] ?? 'NA',
            'last_name' => $names[1] ?? 'NA',
            'email' => (string) $supplier->email,
            'phone_number' => $supplier->phone ?: 'NA',
            'apartment' => 'NA',
            'floor' => 'NA',
            'street' => 'NA',
            'building' => 'NA',
            'city' => 'NA',
            'state' => 'NA',
            'country' => 'EG',
        ];
    }

    private function stringify(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '',
            default => (string) $value,
        };
    }

    private function isTrue(mixed $value): bool
    {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }
}
