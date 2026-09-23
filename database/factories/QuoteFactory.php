<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Enums\VatMode;
use App\Models\Quote;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(50, 400);
        $quantity = fake()->numberBetween(100, 1000);

        return [
            'rfq_id' => Rfq::factory(),
            'supplier_id' => User::factory()->supplier(),
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'min_order_qty' => 100,
            'vat_mode' => VatMode::Included,
            'vat_amount' => null,
            'delivery_cost' => 0,
            'expected_delivery_date' => now()->addWeeks(2),
            'validity_days' => 10,
            'payment_terms' => '50% upfront',
            'sample_availability' => 'Free sample',
            'brand_origin' => fake()->country(),
            'extra_specs' => fake()->sentence(),
            'warranty_policy' => 'Instant replacement of non-conforming goods.',
            'status' => QuoteStatus::Pending,
        ];
    }

    public function shortlisted(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => QuoteStatus::Shortlisted]);
    }

    public function selected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuoteStatus::Selected,
            'selected_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuoteStatus::Rejected,
            'rejected_at' => now(),
        ]);
    }
}
