<?php

namespace App\Actions\Supplier;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateSupplierCoverage
{
    /**
     * Saves the governorates the supplier delivers to, the categories they
     * supply and the payment method they accept. These drive which requests
     * appear in the supplier's feed.
     *
     * @param  array{governorate_ids: array<int, int>, category_ids?: ?array<int, int>, payment_method?: ?string}  $data
     */
    public function handle(User $supplier, array $data): void
    {
        $profile = $supplier->supplierProfile;

        DB::transaction(function () use ($profile, $data): void {
            $profile->update(['payment_method' => $data['payment_method'] ?? null]);
            $profile->governorates()->sync($data['governorate_ids']);
            $profile->categories()->sync($data['category_ids'] ?? []);
        });
    }
}
