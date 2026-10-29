<?php

namespace App\Actions\Supplier;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;

class UpdateSupplierCompany
{
    public function __construct(private OtpService $otp) {}

    /**
     * Saves the supplier's own company and contact details. A new email
     * address has to be confirmed again, just like at sign-up.
     *
     * @param  array{company_name: string, name: string, phone: string, email: string, facility_address: ?string, activity_description: ?string}  $data
     * @return bool whether the email changed and now awaits verification
     */
    public function handle(User $supplier, array $data): bool
    {
        $emailChanged = DB::transaction(function () use ($supplier, $data): bool {
            $supplier->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
            ]);

            $emailChanged = $supplier->isDirty('email');

            if ($emailChanged) {
                $supplier->email_verified_at = null;
            }

            $supplier->save();

            $supplier->supplierProfile->update([
                'company_name' => $data['company_name'],
                'facility_address' => $data['facility_address'] ?? null,
                'activity_description' => $data['activity_description'] ?? null,
            ]);

            return $emailChanged;
        });

        if ($emailChanged) {
            $this->otp->startVerification($supplier);
        }

        return $emailChanged && ! $supplier->hasVerifiedEmail();
    }
}
