<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quote = fake()->sentence(16);

        return [
            'author_name' => fake()->name(),
            'author_role_ar' => fake()->jobTitle(),
            'author_role_en' => fake()->jobTitle(),
            'location_ar' => fake()->city(),
            'location_en' => fake()->city(),
            'quote_ar' => $quote,
            'quote_en' => $quote,
            'rating' => 5,
            'is_published' => true,
            'sort' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }
}
