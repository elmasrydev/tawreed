<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data is always seeded. The demo dataset is seeded too unless
     * `DEMO_SEED=false`, so a fresh install is immediately reviewable.
     */
    public function run(): void
    {
        $this->call([
            BusinessTypeSeeder::class,
            GovernorateSeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            RejectionReasonSeeder::class,
            PlanSeeder::class,
            AdminSeeder::class,
        ]);

        if (env('DEMO_SEED', true)) {
            $this->call(DemoSeeder::class);
        }
    }
}
