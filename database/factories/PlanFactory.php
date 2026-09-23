<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name_ar' => 'شهر واحد',
            'name_en' => '1 month',
            'description_ar' => 'فتح المحادثات وبيانات المشترين',
            'description_en' => 'Unlock chats and buyer details',
            'days' => 30,
            'price_egp' => 250,
            'trial_days' => 0,
            'is_best_value' => false,
            'is_active' => true,
            'sort' => 0,
        ];
    }
}
