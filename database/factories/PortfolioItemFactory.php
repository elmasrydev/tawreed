<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\SupplierProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioItem>
 */
class PortfolioItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_profile_id' => SupplierProfile::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'client_type' => fake()->randomElement(['Restaurant chain', 'Hotel', 'Café', 'Bakery']),
            'year' => fake()->numberBetween(2018, (int) now()->year),
        ];
    }
}
