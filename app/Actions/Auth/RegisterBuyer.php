<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\BuyerProfile;
use App\Models\User;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;

class RegisterBuyer
{
    public function __construct(private OtpService $otp) {}

    /**
     * @param  array{name: string, job_title: ?string, phone: string, email: string, password: string, company_name: string, company_address: ?string, business_type_id: int, governorate_id: int, commercial_reg_no: ?string, tax_card_no: ?string, locale?: string}  $data
     */
    public function handle(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => PhoneNumber::normalize($data['phone']),
                'password' => $data['password'],
                'role' => UserRole::Buyer,
                'locale' => $data['locale'] ?? app()->getLocale(),
            ]);

            BuyerProfile::create([
                'user_id' => $user->id,
                'business_type_id' => $data['business_type_id'],
                'governorate_id' => $data['governorate_id'],
                'company_name' => $data['company_name'],
                'company_address' => $data['company_address'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'commercial_reg_no' => $data['commercial_reg_no'] ?? null,
                'tax_card_no' => $data['tax_card_no'] ?? null,
            ]);

            return $user;
        });

        $this->otp->issue($user);

        return $user;
    }
}
