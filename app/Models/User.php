<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'locale', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function buyerProfile(): HasOne
    {
        return $this->hasOne(BuyerProfile::class);
    }

    public function supplierProfile(): HasOne
    {
        return $this->hasOne(SupplierProfile::class);
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class, 'buyer_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'supplier_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'supplier_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'supplier_id');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function buyerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    public function supplierConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'supplier_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'supplier_id');
    }

    public function isBuyer(): bool
    {
        return $this->role === UserRole::Buyer;
    }

    public function isSupplier(): bool
    {
        return $this->role === UserRole::Supplier;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * The subscription that currently unlocks chats and buyer details, if any.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', SubscriptionStatus::unlockingValues())
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();
    }

    /**
     * Suppliers without an active plan keep their data but lose chat and buyer details.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->whereIn('status', SubscriptionStatus::unlockingValues())
            ->where('ends_at', '>', now())
            ->exists();
    }

    /**
     * Filament only admits admins to the panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && ! $this->isSuspended();
    }

    #[Scope]
    protected function role(Builder $query, UserRole $role): void
    {
        $query->where('role', $role);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', UserStatus::Active);
    }
}
