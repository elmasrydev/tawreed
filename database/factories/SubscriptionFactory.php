<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => User::factory()->supplier(),
            'plan_id' => Plan::query()->inRandomOrder()->value('id') ?? Plan::factory(),
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            'status' => SubscriptionStatus::Active,
            'auto_renew' => false,
            'amount_egp' => 250,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Expired,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ends_at' => now()->addDays(5),
        ]);
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SubscriptionStatus::Trial,
            'ends_at' => now()->addDays(7),
            'amount_egp' => 0,
        ]);
    }
}
