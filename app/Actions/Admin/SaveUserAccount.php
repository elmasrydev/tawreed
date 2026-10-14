<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\BuyerProfile;
use App\Models\SupplierProfile;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveUserAccount
{
    public function __construct(private DecideSupplierVerification $decideVerification) {}

    /**
     * Creates or updates an account from the admin panel together with its
     * buyer or supplier profile. Accounts an admin creates are trusted, so
     * they skip the emailed code. The role is fixed once an account exists.
     *
     * @param  array{name: string, email: string, phone: string, password?: ?string, role?: string, status?: string, buyer?: array<string, mixed>, supplier?: array<string, mixed>}  $data
     */
    public function handle(array $data, User $admin, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $admin, $user): User {
            $isNew = $user === null;
            $user ??= new User(['role' => UserRole::from($data['role'])]);

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => PhoneNumber::normalize($data['phone']),
                'status' => isset($data['status']) ? UserStatus::from($data['status']) : ($user->status ?? UserStatus::Active),
            ]);

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }

            if ($isNew) {
                $user->locale = app()->getLocale();
                $user->email_verified_at = now();
                $user->phone_verified_at = now();
            }

            $user->save();

            match ($user->role) {
                UserRole::Buyer => $this->saveBuyerProfile($user, $data['buyer'] ?? []),
                UserRole::Supplier => $this->saveSupplierProfile($user, $data['supplier'] ?? [], $admin, $isNew),
                UserRole::Admin => null,
            };

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveBuyerProfile(User $user, array $data): void
    {
        BuyerProfile::updateOrCreate(
            ['user_id' => $user->id],
            Arr::only($data, [
                'company_name', 'company_address', 'job_title', 'business_type_id',
                'governorate_id', 'commercial_reg_no', 'tax_card_no',
            ]),
        );
    }

    /**
     * Only a new supplier's profile is created here; afterwards the profile is
     * edited on the supplier page and verified through the decision actions.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveSupplierProfile(User $user, array $data, User $admin, bool $isNew): void
    {
        if (! $isNew) {
            return;
        }

        $profile = SupplierProfile::create([
            'user_id' => $user->id,
            ...Arr::only($data, [
                'company_name', 'commercial_reg_no', 'tax_number', 'facility_address',
                'activity_description', 'payment_method', 'years_active',
            ]),
            'verification_status' => VerificationStatus::Pending,
        ]);

        $profile->governorates()->sync($data['governorate_ids'] ?? []);
        $profile->categories()->sync($data['category_ids'] ?? []);

        $status = VerificationStatus::from($data['verification_status'] ?? VerificationStatus::Pending->value);

        if ($status !== VerificationStatus::Pending) {
            $this->decideVerification->handle($profile, $status, $admin);
        }
    }
}
