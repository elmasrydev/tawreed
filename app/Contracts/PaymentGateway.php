<?php

namespace App\Contracts;

use App\Exceptions\PaymentFailedException;
use App\Models\Plan;
use App\Models\User;

/**
 * The prototype has no payment provider. Swapping in Paymob or Fawry later
 * means binding another implementation of this contract.
 */
interface PaymentGateway
{
    /**
     * Charges the supplier for a plan and returns the provider's reference.
     *
     * @throws PaymentFailedException
     */
    public function charge(User $supplier, Plan $plan): string;
}
