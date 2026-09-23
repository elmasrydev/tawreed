<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = 250;

        return [
            'subscription_id' => Subscription::factory(),
            'supplier_id' => fn (array $attributes) => Subscription::find($attributes['subscription_id'])?->supplier_id,
            'number' => 'INV-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'amount_egp' => $amount,
            'vat_egp' => 0,
            'total_egp' => $amount,
            'issued_at' => now(),
        ];
    }
}
