<?php

namespace Database\Seeders;

use App\Models\RejectionReason;
use Illuminate\Database\Seeder;

class RejectionReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['price-too-high', 'السعر مرتفع', 'Price too high'],
            ['delivery-time', 'مدة التسليم غير مناسبة', 'Delivery time unsuitable'],
            ['specs-mismatch', 'المواصفات غير مطابقة', "Specs don't match"],
            ['payment-terms', 'شروط الدفع غير مناسبة', 'Payment terms unsuitable'],
            ['chose-another', 'اخترت موردًا آخر', 'Chose another supplier'],
        ];

        foreach ($reasons as $sort => [$slug, $ar, $en]) {
            RejectionReason::updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $ar, 'name_en' => $en, 'sort' => $sort],
            );
        }
    }
}
