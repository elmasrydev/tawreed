<?php

namespace Database\Seeders;

use App\Actions\Chat\SendMessage;
use App\Actions\Quote\SelectQuote;
use App\Actions\Quote\SubmitQuote;
use App\Enums\MessageType;
use App\Enums\RfqStatus;
use App\Enums\VatMode;
use App\Models\Review;
use App\Models\Rfq;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A completed deal: the buyer awarded a quote, which opened the private chat
 * and revealed contact details, and later left a review.
 */
class DemoDealSeeder extends Seeder
{
    public function run(): void
    {
        $rfq = Rfq::where('title', 'سكر أبيض معبأ — 2 طن')->first();
        $winner = User::where('email', 'nile@tawreedhub.test')->first();
        $loser = User::where('email', 'nour@tawreedhub.test')->first();

        if ($rfq === null || $winner === null || $loser === null || $rfq->awarded_quote_id !== null) {
            return;
        }

        // The request must accept quotes while the competing offers are placed.
        $rfq->forceFill(['status' => RfqStatus::Open])->save();

        $winningQuote = app(SubmitQuote::class)->handle($rfq, $winner, [
            'unit_price' => 31000,
            'total_price' => 62000,
            'min_order_qty' => 1,
            'vat_mode' => VatMode::Included,
            'delivery_cost' => 0,
            'expected_delivery_date' => now()->addDays(6),
            'validity_days' => 14,
            'payment_terms' => '50% مقدم والباقي عند التسليم',
            'sample_availability' => 'متاحة مجانًا',
            'brand_origin' => 'مصر',
            'extra_specs' => 'تعبئة أكياس 1 كجم، توريد شهري منتظم.',
        ]);

        app(SubmitQuote::class)->handle($rfq, $loser, [
            'unit_price' => 33500,
            'total_price' => 67000,
            'vat_mode' => VatMode::Excluded,
            'vat_amount' => 9380,
            'delivery_cost' => 1200,
            'expected_delivery_date' => now()->addDays(9),
            'validity_days' => 7,
            'payment_terms' => 'كاش عند التسليم',
            'sample_availability' => 'برسوم رمزية',
            'brand_origin' => 'مصر',
        ]);

        $conversation = app(SelectQuote::class)->handle($winningQuote);

        $buyer = $rfq->buyer;
        $sendMessage = app(SendMessage::class);

        $script = [
            [$winner, 'أهلًا بحضرتك، شكرًا لاختيار عرضنا. هنبدأ تجهيز الشحنة الأولى فورًا.'],
            [$buyer, 'أهلًا أستاذ إيهاب. ممكن نستلم عينة 1 كجم قبل الشحنة الكاملة؟'],
            [$winner, 'تمام، العينة مجانية وهتوصل خلال 48 ساعة على عنوان المقر.'],
            [$buyer, 'ممتاز. والتسليم هيكون على دفعة واحدة ولا دفعتين؟'],
            [$winner, 'دفعة واحدة في الموعد المتفق عليه، وهرفق الفاتورة المبدئية هنا.'],
        ];

        foreach ($script as [$sender, $body]) {
            $sendMessage->handle($conversation, $sender, $body, MessageType::Text);
        }

        $sendMessage->markRead($conversation, $buyer);

        Review::updateOrCreate(
            ['rfq_id' => $rfq->id, 'buyer_id' => $buyer->id],
            [
                'supplier_id' => $winner->id,
                'rating' => 5,
                'body' => 'التزام ممتاز بمواعيد التسليم وجودة ثابتة. التواصل سريع والتعبئة احترافية.',
            ],
        );

        $this->seedHistoricReviews();
        $this->refreshSupplierRatings();
    }

    /**
     * Keeps the cached rating columns in step with the reviews just written, so
     * a profile never shows a score its reviews do not support.
     */
    private function refreshSupplierRatings(): void
    {
        SupplierProfile::query()->with('user')->each(function (SupplierProfile $profile): void {
            $reviews = Review::where('supplier_id', $profile->user_id);
            $count = $reviews->count();

            if ($count === 0) {
                return;
            }

            $profile->forceFill([
                'rating_avg' => round((float) $reviews->avg('rating'), 2),
                'reviews_count' => $count,
            ])->save();
        });
    }

    /**
     * Earlier reviews so a supplier profile does not read as brand new.
     */
    private function seedHistoricReviews(): void
    {
        $delta = User::where('email', 'supplier@tawreedhub.test')->first();
        $rfq = Rfq::where('title', 'بن أسبريسو محمّص — 500 كجم')->first();

        if ($delta === null || $rfq === null) {
            return;
        }

        $entries = [
            ['hotel@tawreedhub.test', 5, 'جودة البن ممتازة والتغليف احترافي عبر ثلاث توريدات متتالية.'],
            ['school@tawreedhub.test', 4, 'أرسلوا عينة قبل التعاقد وطابقت المواصفات تمامًا.'],
        ];

        foreach ($entries as [$email, $rating, $body]) {
            $buyer = User::where('email', $email)->first();

            if ($buyer === null) {
                continue;
            }

            Review::updateOrCreate(
                ['rfq_id' => $rfq->id, 'buyer_id' => $buyer->id],
                ['supplier_id' => $delta->id, 'rating' => $rating, 'body' => $body],
            );
        }
    }
}
