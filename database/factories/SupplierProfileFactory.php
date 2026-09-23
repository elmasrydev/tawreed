<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\SupplierProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierProfile>
 */
class SupplierProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'commercial_reg_no' => fake()->numerify('######'),
            'tax_number' => fake()->numerify('###-###-###'),
            'facility_address' => fake()->streetAddress(),
            'activity_description' => fake()->sentence(),
            'payment_method' => 'Bank transfer',
            'years_active' => fake()->numberBetween(1, 25),
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_status' => VerificationStatus::Pending,
            'verified_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_status' => VerificationStatus::Rejected,
            'verified_at' => null,
            'verification_note' => 'Unclear document — re-upload the commercial registration.',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verification_status' => VerificationStatus::Suspended,
        ]);
    }
}
