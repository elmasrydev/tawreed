<?php

namespace App\Actions\Auth;

use App\Enums\SupplierDocumentType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\SupplierDocument;
use App\Models\SupplierProfile;
use App\Models\User;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RegisterSupplier
{
    public function __construct(private OtpService $otp) {}

    /**
     * Suppliers land in the verification queue; they may browse requests but
     * cannot quote until an admin approves their documents.
     *
     * @param  array{name: string, phone: string, email: string, password: string, company_name: string, commercial_reg_no: string, tax_number: string, facility_address: ?string, activity_description: ?string, payment_method: ?string, governorate_ids: array<int, int>, category_ids?: array<int, int>, locale?: string}  $data
     * @param  array<string, UploadedFile>  $documents  keyed by SupplierDocumentType value
     */
    public function handle(array $data, array $documents = []): User
    {
        $user = DB::transaction(function () use ($data, $documents): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => PhoneNumber::normalize($data['phone']),
                'password' => $data['password'],
                'role' => UserRole::Supplier,
                'locale' => $data['locale'] ?? app()->getLocale(),
            ]);

            $profile = SupplierProfile::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'commercial_reg_no' => $data['commercial_reg_no'],
                'tax_number' => $data['tax_number'],
                'facility_address' => $data['facility_address'] ?? null,
                'activity_description' => $data['activity_description'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'verification_status' => VerificationStatus::Pending,
            ]);

            $profile->governorates()->sync($data['governorate_ids']);
            $profile->categories()->sync($data['category_ids'] ?? []);

            foreach ($documents as $type => $file) {
                $document = SupplierDocument::create([
                    'supplier_profile_id' => $profile->id,
                    'type' => SupplierDocumentType::from($type),
                ]);

                $document->addMedia($file)->toMediaCollection('file');
            }

            return $user;
        });

        $this->otp->issue($user);

        return $user;
    }
}
