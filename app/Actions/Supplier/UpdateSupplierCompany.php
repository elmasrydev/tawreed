<?php

namespace App\Actions\Supplier;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateSupplierCompany
{
    /**
     * Saves the supplier's own company details. The phone and email are
     * sign-in credentials, so only an admin changes them.
     *
     * @param  array{company_name: string, name: string, facility_address: ?string, activity_description: ?string}  $data
     */
    public function handle(User $supplier, array $data): void
    {
        DB::transaction(function () use ($supplier, $data): void {
            $supplier->update(['name' => $data['name']]);

            $supplier->supplierProfile->update([
                'company_name' => $data['company_name'],
                'facility_address' => $data['facility_address'] ?? null,
                'activity_description' => $data['activity_description'] ?? null,
            ]);
        });
    }
}
