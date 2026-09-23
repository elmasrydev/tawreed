<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Message;
use App\Models\Quote;
use App\Models\Report;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Moderation queue: two open reports for an admin to act on, plus one already
 * resolved so the history is not empty.
 */
class DemoReportSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@tawreedhub.test')->first();
        $buyer = User::where('email', 'buyer@tawreedhub.test')->first();
        $quote = Quote::query()->oldest('id')->first();
        $rfq = Rfq::where('title', 'ورق تصوير A4 80 جم — 300 رزمة')->first();
        $message = Message::query()->whereNotNull('sender_id')->oldest('id')->first();

        if ($quote === null || $buyer === null) {
            return;
        }

        Report::updateOrCreate(
            ['reportable_type' => Quote::class, 'reportable_id' => $quote->id, 'type' => ReportType::ContactDetailsBypass],
            [
                'reporter_id' => null,
                'reason' => 'رصد النظام رقم هاتف داخل حقل «مواصفات إضافية» في العرض — محاولة تجاوز المنصة.',
                'status' => ReportStatus::Open,
            ],
        );

        if ($rfq !== null) {
            Report::updateOrCreate(
                ['reportable_type' => Rfq::class, 'reportable_id' => $rfq->id, 'type' => ReportType::DuplicateRfq],
                [
                    'reporter_id' => $buyer->id,
                    'reason' => 'نُشر الطلب أربع مرات خلال ساعة واحدة من نفس الحساب.',
                    'status' => ReportStatus::Open,
                ],
            );
        }

        if ($message !== null && $admin !== null) {
            $report = Report::updateOrCreate(
                ['reportable_type' => Message::class, 'reportable_id' => $message->id, 'type' => ReportType::InappropriateContent],
                [
                    'reporter_id' => $buyer->id,
                    'reason' => 'بلاغ عن لغة غير لائقة أثناء التفاوض على السعر.',
                    'status' => ReportStatus::Dismissed,
                ],
            );

            $report->forceFill([
                'resolved_by' => $admin->id,
                'resolution_note' => 'تمت مراجعة المحادثة ولم يُرصد مخالفة. تم تجاهل البلاغ.',
                'resolved_at' => now()->subDay(),
            ])->save();
        }
    }
}
