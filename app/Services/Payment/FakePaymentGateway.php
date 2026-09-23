<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Always succeeds and returns a synthetic reference, so the subscription flow
 * can be demonstrated end to end without a provider account.
 */
class FakePaymentGateway implements PaymentGateway
{
    public function charge(User $supplier, Plan $plan): string
    {
        return 'DEMO-'.Str::upper(Str::random(10));
    }
}
