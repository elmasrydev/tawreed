<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Quote;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory()->buyer(),
            'reportable_type' => Quote::class,
            'reportable_id' => Quote::factory(),
            'type' => ReportType::NonCompliantQuote,
            'reason' => fake()->sentence(),
            'status' => ReportStatus::Open,
        ];
    }
}
