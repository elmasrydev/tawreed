<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => User::factory()->supplier(),
            'plan_id' => Plan::query()->inRandomOrder()->value('id') ?? Plan::factory(),
            'gateway' => 'paymob',
            'reference' => (string) Str::uuid(),
            'amount_egp' => 250,
            'vat_egp' => 35,
            'total_egp' => 285,
            'status' => PaymentStatus::Pending,
        ];
    }

    /**
     * Prices the payment from a plan, VAT included, as checkout does.
     */
    public function forPlan(Plan $plan): static
    {
        $vat = round((float) $plan->price_egp * 0.14, 2);

        return $this->state(fn (array $attributes): array => [
            'plan_id' => $plan->id,
            'amount_egp' => $plan->price_egp,
            'vat_egp' => $vat,
            'total_egp' => (float) $plan->price_egp + $vat,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Paid,
            'provider_transaction_id' => (string) fake()->unique()->randomNumber(9),
            'paid_at' => now(),
        ]);
    }
}
