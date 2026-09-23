<?php

namespace Database\Factories;

use App\Models\RejectionReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RejectionReason>
 */
class RejectionReasonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'slug' => str($name)->slug()->toString(),
            'name_ar' => $name,
            'name_en' => $name,
            'sort' => 0,
        ];
    }
}
