<?php

namespace Database\Factories;

use App\Models\BusinessType;
use App\Models\BuyerProfile;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuyerProfile>
 */
class BuyerProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_type_id' => BusinessType::query()->inRandomOrder()->value('id')
                ?? BusinessType::factory(),
            'governorate_id' => Governorate::query()->inRandomOrder()->value('id')
                ?? Governorate::factory(),
            'company_name' => fake()->company(),
            'company_address' => fake()->streetAddress(),
            'job_title' => 'Purchasing Manager',
            'commercial_reg_no' => fake()->numerify('######'),
            'tax_card_no' => fake()->numerify('###-###-###'),
        ];
    }
}
