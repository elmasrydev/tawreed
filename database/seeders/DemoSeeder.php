<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Populates the whole marketplace with a realistic, self-consistent dataset so
 * the app can be reviewed as though it had been running for a few months.
 *
 * Order matters: accounts, then their plans, then requests, quotes, the awarded
 * deal with its chat, and finally the moderation queue that references them.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoCategoryImageSeeder::class,
            DemoAccountSeeder::class,
            DemoSubscriptionSeeder::class,
            DemoRfqSeeder::class,
            DemoQuoteSeeder::class,
            DemoDealSeeder::class,
            DemoReportSeeder::class,
        ]);
    }
}
