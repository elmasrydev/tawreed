<?php

namespace Database\Factories;

use App\Enums\RfqStatus;
use App\Enums\SupplyType;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Rfq;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rfq>
 */
class RfqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory()->buyer(),
            'category_id' => Category::query()->whereNull('parent_id')->inRandomOrder()->value('id')
                ?? Category::factory(),
            'subcategory_id' => null,
            'unit_id' => Unit::query()->inRandomOrder()->value('id') ?? Unit::factory(),
            'governorate_id' => Governorate::query()->inRandomOrder()->value('id')
                ?? Governorate::factory(),
            'title' => fake()->words(4, true),
            'specs' => fake()->paragraph(),
            'quantity' => fake()->numberBetween(50, 5000),
            'delivery_date' => now()->addWeeks(3),
            'supply_type' => SupplyType::OneTime,
            'quote_deadline' => now()->addWeek(),
            'notes' => fake()->sentence(),
            'status' => RfqStatus::Open,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RfqStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function endingSoon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quote_deadline' => now()->addDay(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RfqStatus::Expired,
            'quote_deadline' => now()->subDay(),
        ]);
    }

    public function awarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RfqStatus::Awarded,
            'awarded_at' => now(),
        ]);
    }
}
