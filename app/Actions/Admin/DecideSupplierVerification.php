<?php

namespace App\Actions\Admin;

use App\Enums\VerificationStatus;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DecideSupplierVerification
{
    /**
     * Records the verification team's decision. Approving grants the badge that
     * buyers see on every quote, so the deciding admin is recorded with it.
     */
    public function handle(
        SupplierProfile $profile,
        VerificationStatus $status,
        User $admin,
        ?string $note = null,
    ): SupplierProfile {
        return DB::transaction(function () use ($profile, $status, $admin, $note): SupplierProfile {
            $profile->forceFill([
                'verification_status' => $status,
                'verification_note' => $note,
                'verified_at' => $status === VerificationStatus::Verified ? now() : null,
                'verified_by' => $admin->id,
            ])->save();

            return $profile;
        });
    }
}
