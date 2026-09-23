<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        $governorates = [
            ['cairo', 'القاهرة', 'Cairo'],
            ['giza', 'الجيزة', 'Giza'],
            ['alexandria', 'الإسكندرية', 'Alexandria'],
            ['qalyubia', 'القليوبية', 'Qalyubia'],
            ['sharqia', 'الشرقية', 'Sharqia'],
            ['dakahlia', 'الدقهلية', 'Dakahlia'],
            ['gharbia', 'الغربية', 'Gharbia'],
            ['monufia', 'المنوفية', 'Monufia'],
            ['beheira', 'البحيرة', 'Beheira'],
            ['kafr-el-sheikh', 'كفر الشيخ', 'Kafr El Sheikh'],
            ['damietta', 'دمياط', 'Damietta'],
            ['port-said', 'بورسعيد', 'Port Said'],
            ['ismailia', 'الإسماعيلية', 'Ismailia'],
            ['suez', 'السويس', 'Suez'],
            ['north-sinai', 'شمال سيناء', 'North Sinai'],
            ['south-sinai', 'جنوب سيناء', 'South Sinai'],
            ['beni-suef', 'بني سويف', 'Beni Suef'],
            ['faiyum', 'الفيوم', 'Faiyum'],
            ['minya', 'المنيا', 'Minya'],
            ['assiut', 'أسيوط', 'Assiut'],
            ['sohag', 'سوهاج', 'Sohag'],
            ['qena', 'قنا', 'Qena'],
            ['luxor', 'الأقصر', 'Luxor'],
            ['aswan', 'أسوان', 'Aswan'],
            ['red-sea', 'البحر الأحمر', 'Red Sea'],
            ['new-valley', 'الوادي الجديد', 'New Valley'],
            ['matrouh', 'مطروح', 'Matrouh'],
        ];

        foreach ($governorates as $sort => [$slug, $ar, $en]) {
            Governorate::updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $ar, 'name_en' => $en, 'sort' => $sort],
            );
        }
    }
}
