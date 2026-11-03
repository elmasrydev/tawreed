<?php

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideSupplierVerification
{
    /**
     * Records the verification team's decision. Approving grants the badge that
     * buyers see on every quote, so the deciding admin is recorded with it.
     * Suspending also locks the account out of signing in, and any other
     * decision lets a suspended account back in. The badge is only granted
     * once every required document has been accepted.
     *
     * @throws ValidationException
     */
    public function handle(
        SupplierProfile $profile,
        VerificationStatus $status,
        User $admin,
        ?string $note = null,
    ): SupplierProfile {
        if ($status === VerificationStatus::Verified && ! $profile->hasAcceptedRequiredDocuments()) {
            throw ValidationException::withMessages([
                'verification_status' => __('admin.verify_requires_documents'),
            ]);
        }

        return DB::transaction(function () use ($profile, $status, $admin, $note): SupplierProfile {
            $profile->forceFill([
                'verification_status' => $status,
                'verification_note' => $note,
                'verified_at' => $status === VerificationStatus::Verified ? now() : null,
                'verified_by' => $admin->id,
            ])->save();

            $profile->user->forceFill([
                'status' => $status === VerificationStatus::Suspended ? UserStatus::Suspended : UserStatus::Active,
            ])->save();

            return $profile;
        });
    }
}
