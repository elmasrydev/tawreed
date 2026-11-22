<?php

namespace App\Actions\Buyer;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateBuyerProfile
{
    /**
     * Saves the buyer's own organisation details. The phone and email are
     * sign-in credentials, so only an admin changes them.
     *
     * @param  array{name: string, job_title: ?string, company_name: string, company_address: ?string, business_type_id: int, governorate_id: int, commercial_reg_no: ?string, tax_card_no: ?string}  $data
     */
    public function handle(User $buyer, array $data): void
    {
        DB::transaction(function () use ($buyer, $data): void {
            $buyer->update(['name' => $data['name']]);

            $buyer->buyerProfile->update([
                'business_type_id' => $data['business_type_id'],
                'governorate_id' => $data['governorate_id'],
                'company_name' => $data['company_name'],
                'company_address' => $data['company_address'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'commercial_reg_no' => $data['commercial_reg_no'] ?? null,
                'tax_card_no' => $data['tax_card_no'] ?? null,
            ]);
        });
    }
}
