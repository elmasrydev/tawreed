<?php

namespace Database\Seeders;

use App\Enums\RfqStatus;
use App\Enums\SupplyType;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Rfq;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Supply requests across every buyer, category and lifecycle state: open,
 * ending soon, awarded, expired and an unpublished draft.
 */
class DemoRfqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->requests() as $spec) {
            $buyer = User::where('email', $spec['buyer'])->first();

            if ($buyer === null) {
                continue;
            }

            $rfq = Rfq::updateOrCreate(
                ['buyer_id' => $buyer->id, 'title' => $spec['title']],
                [
                    'category_id' => Category::where('slug', $spec['category'])->value('id'),
                    'subcategory_id' => Category::where('slug', $spec['subcategory'])->value('id'),
                    'unit_id' => Unit::where('slug', $spec['unit'])->value('id'),
                    'governorate_id' => Governorate::where('slug', $spec['governorate'])->value('id'),
                    'specs' => $spec['specs'],
                    'quantity' => $spec['quantity'],
                    'delivery_date' => now()->addDays($spec['deliver_in']),
                    'supply_type' => ($spec['recurring'] ?? false) ? SupplyType::Recurring : SupplyType::OneTime,
                    'recurrence_note' => $spec['recurrence'] ?? null,
                    'quote_deadline' => now()->addDays($spec['deadline_in']),
                    'notes' => $spec['notes'] ?? null,
                    'status' => $spec['status'],
                ],
            );

            $rfq->forceFill([
                'published_at' => $spec['status'] === RfqStatus::Draft
                    ? null
                    : now()->subDays($spec['posted_days_ago']),
            ])->save();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function requests(): array
    {
        return [
            [
                'buyer' => 'buyer@tawreedhub.test', 'title' => 'بن أسبريسو محمّص — 500 كجم',
                'category' => 'beverages', 'subcategory' => 'coffee-hot-drinks', 'unit' => 'kilogram',
                'governorate' => 'cairo', 'quantity' => 500,
                'specs' => 'خلطة 80% أرابيكا / 20% روبوستا، تحميص متوسط-غامق، حبوب كاملة، تعبئة أكياس 1 كجم بصمام هواء.',
                'notes' => 'يشترط توفر عينة 2 كجم قبل التعاقد النهائي.',
                'deliver_in' => 14, 'deadline_in' => 6, 'posted_days_ago' => 3, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'buyer@tawreedhub.test', 'title' => 'أكواب ورقية 12oz — 20,000 قطعة',
                'category' => 'packaging', 'subcategory' => 'paper-cups', 'unit' => 'piece',
                'governorate' => 'cairo', 'quantity' => 20000,
                'specs' => 'أكواب ورقية مزدوجة الجدار سعة 12 أونصة، طباعة شعار بلون واحد، صالحة للمشروبات الساخنة.',
                'deliver_in' => 20, 'deadline_in' => 9, 'posted_days_ago' => 2, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'buyer@tawreedhub.test', 'title' => 'مياه معدنية 600 مل — 400 كرتونة',
                'category' => 'beverages', 'subcategory' => 'bottled-water', 'unit' => 'carton',
                'governorate' => 'cairo', 'quantity' => 400,
                'specs' => 'كرتونة 12 زجاجة، صلاحية لا تقل عن 10 أشهر، توريد كل أسبوعين.',
                'recurring' => true, 'recurrence' => 'كل أسبوعين',
                'deliver_in' => 10, 'deadline_in' => 2, 'posted_days_ago' => 5, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'buyer@tawreedhub.test', 'title' => 'سكر أبيض معبأ — 2 طن',
                'category' => 'food', 'subcategory' => 'sugar-sweeteners', 'unit' => 'ton',
                'governorate' => 'cairo', 'quantity' => 2,
                'specs' => 'سكر أبيض نقي معبأ أكياس 1 كجم، توريد شهري منتظم.',
                'recurring' => true, 'recurrence' => 'شهريًا',
                'deliver_in' => 8, 'deadline_in' => -2, 'posted_days_ago' => 20, 'status' => RfqStatus::Awarded,
            ],
            [
                'buyer' => 'hotel@tawreedhub.test', 'title' => 'أطقم مفروشات أسرّة — 200 طقم',
                'category' => 'textiles', 'subcategory' => 'bed-linen', 'unit' => 'set',
                'governorate' => 'red-sea', 'quantity' => 200,
                'specs' => 'قطن 100%، كثافة 240 خيطًا، أبيض سادة مع شعار مطرّز على المخدة.',
                'deliver_in' => 25, 'deadline_in' => 11, 'posted_days_ago' => 4, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'hotel@tawreedhub.test', 'title' => 'زيت طعام — 150 عبوة 10 لتر',
                'category' => 'food', 'subcategory' => 'cooking-oils', 'unit' => 'tin',
                'governorate' => 'red-sea', 'quantity' => 150,
                'specs' => 'زيت عباد شمس، عبوة 10 لتر، يشترط شهادة سلامة غذائية سارية.',
                'notes' => 'الشهادة الصحية مطلوبة مع العرض.',
                'deliver_in' => 9, 'deadline_in' => 1, 'posted_days_ago' => 6, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'school@tawreedhub.test', 'title' => 'كراسات مدرسية 80 ورقة — 2,000 قطعة',
                'category' => 'stationery', 'subcategory' => 'notebooks', 'unit' => 'piece',
                'governorate' => 'giza', 'quantity' => 2000,
                'specs' => 'غلاف مقوى بطباعة موحدة، ورق 70 جم، تجليد دبابيس.',
                'deliver_in' => 21, 'deadline_in' => 8, 'posted_days_ago' => 3, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'school@tawreedhub.test', 'title' => 'ورق تصوير A4 80 جم — 300 رزمة',
                'category' => 'stationery', 'subcategory' => 'copy-paper', 'unit' => 'ream',
                'governorate' => 'giza', 'quantity' => 300,
                'specs' => 'ورق تصوير أبيض مقاس A4 وزن 80 جم، 500 ورقة للرزمة، تسليم على دفعتين.',
                'deliver_in' => 18, 'deadline_in' => 12, 'posted_days_ago' => 1, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'hospital@tawreedhub.test', 'title' => 'قفازات نيتريل — 300 كرتونة',
                'category' => 'medical-supplies', 'subcategory' => 'gloves-ppe', 'unit' => 'carton',
                'governorate' => 'alexandria', 'quantity' => 300,
                'specs' => 'مقاسات M/L، خالية من البودرة، شهادة CE، صلاحية لا تقل عن سنتين.',
                'deliver_in' => 12, 'deadline_in' => 2, 'posted_days_ago' => 4, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'hospital@tawreedhub.test', 'title' => 'مستهلكات طبية متنوعة — 120 كرتونة',
                'category' => 'medical-supplies', 'subcategory' => 'disposables', 'unit' => 'carton',
                'governorate' => 'alexandria', 'quantity' => 120,
                'specs' => 'سرنجات ومحاليل تعقيم وشاش طبي، قائمة تفصيلية مرفقة مع الطلب.',
                'deliver_in' => 15, 'deadline_in' => -4, 'posted_days_ago' => 25, 'status' => RfqStatus::Expired,
            ],
            [
                'buyer' => 'contractor@tawreedhub.test', 'title' => 'أسمنت بورتلاندي — 800 شيكارة',
                'category' => 'building-materials', 'subcategory' => 'cement', 'unit' => 'bag',
                'governorate' => 'giza', 'quantity' => 800,
                'specs' => 'رتبة 42.5، توريد على موقع إنشاءات بمدينة 6 أكتوبر على ثلاث دفعات.',
                'deliver_in' => 16, 'deadline_in' => 7, 'posted_days_ago' => 2, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'contractor@tawreedhub.test', 'title' => 'حديد تسليح 12 مم — 40 طن',
                'category' => 'building-materials', 'subcategory' => 'steel', 'unit' => 'ton',
                'governorate' => 'giza', 'quantity' => 40,
                'specs' => 'حديد تسليح مقاس 12 مم، شهادة مطابقة مصرية، توريد على دفعتين.',
                'deliver_in' => 30, 'deadline_in' => 14, 'posted_days_ago' => 1, 'status' => RfqStatus::Open,
            ],
            [
                'buyer' => 'contractor@tawreedhub.test', 'title' => 'دهانات بلاستيك — 500 عبوة',
                'category' => 'building-materials', 'subcategory' => 'paints', 'unit' => 'tin',
                'governorate' => 'giza', 'quantity' => 500,
                'specs' => 'دهان بلاستيك داخلي أبيض، عبوة 9 لتر. مسودة قيد الإعداد.',
                'deliver_in' => 40, 'deadline_in' => 20, 'posted_days_ago' => 0, 'status' => RfqStatus::Draft,
            ],
        ];
    }
}
