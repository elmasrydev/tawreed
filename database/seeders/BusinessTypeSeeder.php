<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['company', 'شركة', 'Company'],
            ['restaurant-cafe', 'مطعم / كافيه', 'Restaurant / Café'],
            ['hotel', 'فندق', 'Hotel'],
            ['supermarket', 'سوبر ماركت', 'Supermarket'],
            ['school-university', 'مدرسة / جامعة', 'School / University'],
            ['hospital-clinic', 'مستشفى / عيادة', 'Hospital / Clinic'],
            ['factory', 'مصنع (خامات)', 'Factory (raw materials)'],
            ['contractor', 'مقاول', 'Contractor'],
            ['catering', 'كاترينج', 'Catering'],
            ['office', 'مكتب', 'Office'],
            ['sme', 'شركة صغيرة', 'SME'],
        ];

        foreach ($types as $sort => [$slug, $ar, $en]) {
            BusinessType::updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $ar, 'name_en' => $en, 'sort' => $sort],
            );
        }
    }
}
