<?php

namespace Database\Seeders;

use App\Actions\Quote\RejectQuote;
use App\Actions\Quote\ShortlistQuote;
use App\Actions\Quote\SubmitQuote;
use App\Enums\VatMode;
use App\Models\Quote;
use App\Models\RejectionReason;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Competing quotes on the open requests, in a mix of pending, shortlisted and
 * rejected states so the buyer's comparison screen has something to show.
 */
class DemoQuoteSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->quotes() as $spec) {
            $rfq = Rfq::where('title', $spec['rfq'])->first();
            $supplier = User::where('email', $spec['supplier'])->first();

            if ($rfq === null || $supplier === null) {
                continue;
            }

            if ($rfq->quotes()->where('supplier_id', $supplier->id)->exists()) {
                continue;
            }

            $quote = app(SubmitQuote::class)->handle($rfq, $supplier, [
                'unit_price' => $spec['unit_price'],
                'total_price' => $spec['total_price'],
                'min_order_qty' => $spec['min_qty'] ?? null,
                'vat_mode' => $spec['vat'],
                'vat_amount' => $spec['vat_amount'] ?? null,
                'delivery_cost' => $spec['delivery'] ?? 0,
                'expected_delivery_date' => now()->addDays($spec['deliver_in']),
                'validity_days' => $spec['validity'] ?? 10,
                'payment_terms' => $spec['terms'],
                'sample_availability' => $spec['sample'],
                'brand_origin' => $spec['origin'],
                'extra_specs' => $spec['extra'] ?? null,
                'warranty_policy' => $spec['warranty'] ?? 'استبدال فوري لأي كمية غير مطابقة للمواصفات.',
            ]);

            $this->applyDecision($quote, $spec['decision'] ?? null);
        }
    }

    private function applyDecision(Quote $quote, ?string $decision): void
    {
        match ($decision) {
            'shortlist' => app(ShortlistQuote::class)->handle($quote),
            'reject' => app(RejectQuote::class)->handle(
                $quote,
                RejectionReason::where('slug', 'price-too-high')->firstOrFail(),
            ),
            default => null,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function quotes(): array
    {
        return [
            // Espresso beans — three competing quotes, one shortlisted, one rejected.
            [
                'rfq' => 'بن أسبريسو محمّص — 500 كجم', 'supplier' => 'supplier@tawreedhub.test',
                'unit_price' => 185, 'total_price' => 92500, 'min_qty' => 100,
                'vat' => VatMode::Included, 'delivery' => 0, 'deliver_in' => 12, 'validity' => 10,
                'terms' => '50% مقدم والباقي عند التسليم', 'sample' => 'متاحة مجانًا',
                'origin' => 'البرازيل / فيتنام', 'extra' => 'تعبئة أكياس 1 كجم بصمام هواء.',
                'decision' => 'shortlist',
            ],
            [
                'rfq' => 'بن أسبريسو محمّص — 500 كجم', 'supplier' => 'nile@tawreedhub.test',
                'unit_price' => 178, 'total_price' => 89000, 'min_qty' => 200,
                'vat' => VatMode::Excluded, 'vat_amount' => 12460, 'delivery' => 1500,
                'deliver_in' => 15, 'validity' => 7,
                'terms' => 'كاش عند التسليم', 'sample' => 'برسوم رمزية',
                'origin' => 'إثيوبيا', 'extra' => 'تحميص حسب الطلب قبل الشحن بيومين.',
            ],
            [
                'rfq' => 'بن أسبريسو محمّص — 500 كجم', 'supplier' => 'nour@tawreedhub.test',
                'unit_price' => 196, 'total_price' => 98000,
                'vat' => VatMode::Included, 'delivery' => 2000, 'deliver_in' => 10, 'validity' => 14,
                'terms' => 'آجل 30 يومًا', 'sample' => 'غير متاحة', 'origin' => 'كولومبيا',
                'decision' => 'reject',
            ],

            // Paper cups.
            [
                'rfq' => 'أكواب ورقية 12oz — 20,000 قطعة', 'supplier' => 'alex@tawreedhub.test',
                'unit_price' => 1.55, 'total_price' => 31000, 'min_qty' => 5000,
                'vat' => VatMode::Included, 'delivery' => 0, 'deliver_in' => 18, 'validity' => 12,
                'terms' => '30% مقدم', 'sample' => 'عينة مطبوعة مجانًا', 'origin' => 'مصر',
                'extra' => 'طباعة الشعار بلون واحد على الكوب.',
            ],
            [
                'rfq' => 'أكواب ورقية 12oz — 20,000 قطعة', 'supplier' => 'nour@tawreedhub.test',
                'unit_price' => 1.72, 'total_price' => 34400,
                'vat' => VatMode::Excluded, 'vat_amount' => 4816, 'delivery' => 900,
                'deliver_in' => 14, 'validity' => 10,
                'terms' => '50% مقدم', 'sample' => 'متاحة مجانًا', 'origin' => 'مصر / تركيا',
            ],

            // Mineral water, recurring.
            [
                'rfq' => 'مياه معدنية 600 مل — 400 كرتونة', 'supplier' => 'nour@tawreedhub.test',
                'unit_price' => 72, 'total_price' => 28800, 'min_qty' => 100,
                'vat' => VatMode::Included, 'delivery' => 0, 'deliver_in' => 8, 'validity' => 15,
                'terms' => 'دفع شهري مجمّع', 'sample' => 'كرتونة عينة مجانية', 'origin' => 'مصر',
                'extra' => 'توريد كل أسبوعين بجدول ثابت.',
            ],

            // Hotel linen.
            [
                'rfq' => 'أطقم مفروشات أسرّة — 200 طقم', 'supplier' => 'nile@tawreedhub.test',
                'unit_price' => 690, 'total_price' => 138000, 'min_qty' => 50,
                'vat' => VatMode::Excluded, 'vat_amount' => 19320, 'delivery' => 3500,
                'deliver_in' => 24, 'validity' => 14,
                'terms' => '40% مقدم', 'sample' => 'طقم عينة مجاني', 'origin' => 'مصر',
                'extra' => 'تطريز شعار الفندق على المخدات.',
            ],

            // Cooking oil.
            [
                'rfq' => 'زيت طعام — 150 عبوة 10 لتر', 'supplier' => 'nile@tawreedhub.test',
                'unit_price' => 620, 'total_price' => 93000,
                'vat' => VatMode::Included, 'delivery' => 1200, 'deliver_in' => 8, 'validity' => 7,
                'terms' => 'كاش عند التسليم', 'sample' => 'غير متاحة', 'origin' => 'مصر',
                'extra' => 'شهادة سلامة غذائية سارية مرفقة.',
            ],

            // School notebooks.
            [
                'rfq' => 'كراسات مدرسية 80 ورقة — 2,000 قطعة', 'supplier' => 'alex@tawreedhub.test',
                'unit_price' => 14.5, 'total_price' => 29000, 'min_qty' => 500,
                'vat' => VatMode::Included, 'delivery' => 0, 'deliver_in' => 19, 'validity' => 20,
                'terms' => 'آجل 30 يومًا', 'sample' => 'متاحة مجانًا', 'origin' => 'مصر',
            ],

            // Copy paper.
            [
                'rfq' => 'ورق تصوير A4 80 جم — 300 رزمة', 'supplier' => 'alex@tawreedhub.test',
                'unit_price' => 168, 'total_price' => 50400, 'min_qty' => 50,
                'vat' => VatMode::Excluded, 'vat_amount' => 7056, 'delivery' => 600,
                'deliver_in' => 16, 'validity' => 10,
                'terms' => '50% مقدم', 'sample' => 'رزمة عينة', 'origin' => 'إندونيسيا',
            ],

            // Cement.
            [
                'rfq' => 'أسمنت بورتلاندي — 800 شيكارة', 'supplier' => 'nile@tawreedhub.test',
                'unit_price' => 148, 'total_price' => 118400, 'min_qty' => 200,
                'vat' => VatMode::Included, 'delivery' => 4200, 'deliver_in' => 15, 'validity' => 5,
                'terms' => 'كاش عند كل دفعة', 'sample' => 'غير مطلوبة', 'origin' => 'مصر',
                'extra' => 'توريد على ثلاث دفعات حسب جدول الموقع.',
            ],

            // Steel.
            [
                'rfq' => 'حديد تسليح 12 مم — 40 طن', 'supplier' => 'nile@tawreedhub.test',
                'unit_price' => 41500, 'total_price' => 1660000, 'min_qty' => 10,
                'vat' => VatMode::Excluded, 'vat_amount' => 232400, 'delivery' => 15000,
                'deliver_in' => 28, 'validity' => 3,
                'terms' => '60% مقدم', 'sample' => 'غير مطلوبة', 'origin' => 'مصر',
                'extra' => 'شهادة مطابقة مصرية مرفقة مع كل دفعة.',
            ],
        ];
    }
}
