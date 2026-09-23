<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['kilogram', 'كيلوجرام', 'Kilogram'],
            ['ton', 'طن', 'Ton'],
            ['piece', 'قطعة', 'Piece'],
            ['carton', 'كرتونة', 'Carton'],
            ['bag', 'شيكارة', 'Bag'],
            ['tin', 'عبوة', 'Tin'],
            ['ream', 'رزمة', 'Ream'],
            ['set', 'طقم', 'Set'],
            ['litre', 'لتر', 'Litre'],
            ['metre', 'متر', 'Metre'],
            ['box', 'صندوق', 'Box'],
            ['pack', 'عبوة تجميعية', 'Pack'],
        ];

        foreach ($units as $sort => [$slug, $ar, $en]) {
            Unit::updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $ar, 'name_en' => $en, 'sort' => $sort],
            );
        }
    }
}
