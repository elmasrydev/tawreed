<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => User::factory()->supplier(),
            'buyer_id' => User::factory()->buyer(),
            'rfq_id' => Rfq::factory(),
            'rating' => fake()->numberBetween(4, 5),
            'body' => fake()->sentence(),
        ];
    }
}
