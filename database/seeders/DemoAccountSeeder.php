<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\BusinessType;
use App\Models\BuyerProfile;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo buyers and suppliers. The suppliers deliberately cover every
 * verification state so a reviewer can see each one without editing data.
 */
class DemoAccountSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        foreach ($this->buyers() as $buyer) {
            $this->createBuyer($buyer);
        }

        foreach ($this->suppliers() as $supplier) {
            $this->createSupplier($supplier);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buyers(): array
    {
        return [
            [
                'email' => 'buyer@tawreedhub.test', 'name' => 'أحمد سامي', 'phone' => '01023456789',
                'company' => 'كافيه سيتي', 'type' => 'restaurant-cafe', 'governorate' => 'cairo',
                'address' => 'القاهرة الجديدة، التجمع الخامس', 'job' => 'مدير المشتريات',
                'reg' => '123456', 'tax' => '987-654-321',
            ],
            [
                'email' => 'hotel@tawreedhub.test', 'name' => 'منى عبد الرحمن', 'phone' => '01023456790',
                'company' => 'فندق النخيل', 'type' => 'hotel', 'governorate' => 'red-sea',
                'address' => 'الغردقة، طريق الكورنيش', 'job' => 'مدير المشتريات والتموين',
                'reg' => '223344', 'tax' => '311-220-118',
            ],
            [
                'email' => 'school@tawreedhub.test', 'name' => 'خالد فؤاد', 'phone' => '01023456791',
                'company' => 'مدرسة النور الدولية', 'type' => 'school-university', 'governorate' => 'giza',
                'address' => 'الشيخ زايد، المحور المركزي', 'job' => 'المدير الإداري',
                'reg' => '556677', 'tax' => '442-119-006',
            ],
            [
                'email' => 'hospital@tawreedhub.test', 'name' => 'د. هالة مصطفى', 'phone' => '01023456792',
                'company' => 'مستشفى الشفاء التخصصي', 'type' => 'hospital-clinic', 'governorate' => 'alexandria',
                'address' => 'سموحة، شارع فوزي معاذ', 'job' => 'مدير المشتريات الطبية',
                'reg' => '778899', 'tax' => '551-330-227',
            ],
            [
                'email' => 'contractor@tawreedhub.test', 'name' => 'سامح جابر', 'phone' => '01023456793',
                'company' => 'مقاولون المستقبل', 'type' => 'contractor', 'governorate' => 'giza',
                'address' => 'مدينة 6 أكتوبر، المنطقة الصناعية', 'job' => 'مدير المشروعات',
                'reg' => '990011', 'tax' => '667-441-338',
            ],
        ];
    }

    /**
     * Each supplier represents one state of the verification and subscription
     * lifecycle described in the product brief.
     *
     * @return array<int, array<string, mixed>>
     */
    private function suppliers(): array
    {
        return [
            [
                'email' => 'supplier@tawreedhub.test', 'name' => 'محمود الدلتاوي', 'phone' => '01187654321',
                'company' => 'الدلتا لتحميص البن', 'reg' => '445210', 'tax' => '204-881-337',
                'address' => 'المنطقة الصناعية، العبور', 'years' => 12,
                'activity' => 'تحميص وتوريد البن بالجملة للمقاهي والفنادق — القاهرة والجيزة',
                'status' => VerificationStatus::Verified,
                'governorates' => ['cairo', 'giza', 'qalyubia'],
                'categories' => ['beverages', 'food'],
                'rating' => 4.8, 'reviews' => 24, 'deals' => 61,
            ],
            [
                'email' => 'nile@tawreedhub.test', 'name' => 'إيهاب النجار', 'phone' => '01187654322',
                'company' => 'مطاحن النيل', 'reg' => '445211', 'tax' => '204-881-338',
                'address' => 'المحلة الكبرى، الغربية', 'years' => 18,
                'activity' => 'مطاحن وتوريد حبوب وأرز وسكر بالجملة',
                'status' => VerificationStatus::Verified,
                'governorates' => ['cairo', 'gharbia', 'dakahlia'],
                'categories' => ['food'],
                'rating' => 4.6, 'reviews' => 18, 'deals' => 34,
            ],
            [
                'email' => 'alex@tawreedhub.test', 'name' => 'سيد الإسكندراني', 'phone' => '01187654323',
                'company' => 'الإسكندرية للتوريدات', 'reg' => '445212', 'tax' => '204-881-339',
                'address' => 'العامرية، الإسكندرية', 'years' => 7,
                'activity' => 'توريد مستلزمات التغليف والأكواب الورقية',
                'status' => VerificationStatus::Verified,
                'governorates' => ['alexandria', 'beheira'],
                'categories' => ['packaging', 'stationery'],
                'rating' => 4.1, 'reviews' => 9, 'deals' => 12,
            ],
            [
                'email' => 'nour@tawreedhub.test', 'name' => 'ياسر النور', 'phone' => '01187654324',
                'company' => 'النور لتوريد الأغذية', 'reg' => '445213', 'tax' => '204-881-340',
                'address' => 'مدينة نصر، القاهرة', 'years' => 9,
                'activity' => 'توريد مواد غذائية ومياه معدنية بالجملة',
                'status' => VerificationStatus::Verified,
                'governorates' => ['cairo', 'giza', 'red-sea'],
                'categories' => ['food', 'beverages'],
                'rating' => 4.4, 'reviews' => 15, 'deals' => 27,
            ],
            [
                'email' => 'mahalla@tawreedhub.test', 'name' => 'عبد الله المحلاوي', 'phone' => '01187654325',
                'company' => 'مصنع المحلة للنسيج', 'reg' => '445214', 'tax' => '204-881-341',
                'address' => 'المحلة الكبرى، المنطقة الصناعية', 'years' => 22,
                'activity' => 'نسيج ومفروشات فندقية ويونيفورم',
                'status' => VerificationStatus::Pending,
                'governorates' => ['gharbia', 'cairo'],
                'categories' => ['textiles'],
                'rating' => 0, 'reviews' => 0, 'deals' => 0,
            ],
            [
                'email' => 'saeed@tawreedhub.test', 'name' => 'مصطفى الصعيدي', 'phone' => '01187654326',
                'company' => 'الصعيد للمواد الطبية', 'reg' => '445215', 'tax' => '204-881-342',
                'address' => 'أسيوط، المنطقة الصناعية', 'years' => 5,
                'activity' => 'توريد مستلزمات ومستهلكات طبية',
                'status' => VerificationStatus::Rejected,
                'note' => 'صورة السجل التجاري غير واضحة — برجاء إعادة رفع نسخة أوضح.',
                'governorates' => ['assiut', 'minya'],
                'categories' => ['medical-supplies'],
                'rating' => 0, 'reviews' => 0, 'deals' => 0,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createBuyer(array $data): void
    {
        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => self::PASSWORD,
                'role' => UserRole::Buyer,
                'locale' => 'ar',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );

        BuyerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_type_id' => BusinessType::where('slug', $data['type'])->value('id'),
                'governorate_id' => Governorate::where('slug', $data['governorate'])->value('id'),
                'company_name' => $data['company'],
                'company_address' => $data['address'],
                'job_title' => $data['job'],
                'commercial_reg_no' => $data['reg'],
                'tax_card_no' => $data['tax'],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createSupplier(array $data): void
    {
        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => self::PASSWORD,
                'role' => UserRole::Supplier,
                'locale' => 'ar',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ],
        );

        $profile = SupplierProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $data['company'],
                'commercial_reg_no' => $data['reg'],
                'tax_number' => $data['tax'],
                'facility_address' => $data['address'],
                'activity_description' => $data['activity'],
                'payment_method' => 'تحويل بنكي — CIB',
                'years_active' => $data['years'],
                'verification_status' => $data['status'],
                'verification_note' => $data['note'] ?? null,
            ],
        );

        $profile->forceFill([
            'verified_at' => $data['status'] === VerificationStatus::Verified ? now()->subMonths(3) : null,
            'rating_avg' => $data['rating'],
            'reviews_count' => $data['reviews'],
            'completed_deals_count' => $data['deals'],
        ])->save();

        $profile->governorates()->sync(Governorate::whereIn('slug', $data['governorates'])->pluck('id'));
        $profile->categories()->sync(Category::whereIn('slug', $data['categories'])->pluck('id'));
    }
}
