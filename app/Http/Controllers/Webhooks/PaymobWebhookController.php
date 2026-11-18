<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Subscription\SettlePayment;
use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paymob's "transaction processed" callback. This is the source of truth for
 * whether a subscription payment went through.
 */
class PaymobWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, SettlePayment $settlePayment): Response
    {
        $transaction = $request->input('obj');

        if ($request->input('type') !== 'TRANSACTION' || ! is_array($transaction)) {
            return response()->noContent(200);
        }

        try {
            $result = $gateway->verifyCallback([
                ...Arr::dot($transaction),
                'hmac' => $request->query('hmac', $request->input('hmac')),
            ]);
        } catch (InvalidPaymentSignatureException $exception) {
            Log::warning('Rejected Paymob webhook', ['reason' => $exception->getMessage()]);

            abort(403);
        }

        $settlePayment->handle($result);

        return response()->noContent(200);
    }
}
