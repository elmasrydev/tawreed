<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'trial-15-days',
                'name_ar' => '15 يومًا',
                'name_en' => '15 days',
                'description_ar' => 'تجربة سريعة — كل المزايا لمدة أسبوعين',
                'description_en' => 'Quick trial — all features for two weeks',
                'days' => 15,
                'price_egp' => 150,
                'trial_days' => 0,
                'is_best_value' => false,
                'sort' => 0,
            ],
            [
                'slug' => 'monthly',
                'name_ar' => 'شهر واحد',
                'name_en' => '1 month',
                'description_ar' => 'فتح المحادثات وبيانات المشترين + فواتير إلكترونية',
                'description_en' => 'Unlock chats & buyer details + e-invoices',
                'days' => 30,
                'price_egp' => 250,
                'trial_days' => 0,
                'is_best_value' => false,
                'sort' => 1,
            ],
            [
                'slug' => 'yearly',
                'name_ar' => 'سنة كاملة',
                'name_en' => '1 year',
                'description_ar' => 'وفّر 500 ج.م — يشمل تجربة مجانية 7 أيام للمنضمين الجدد',
                'description_en' => 'Save EGP 500 — includes a 7-day free trial for new members',
                'days' => 365,
                'price_egp' => 2500,
                'trial_days' => 7,
                'is_best_value' => true,
                'sort' => 2,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
