<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("enums.subscription_status.{$this->value}");
    }

    /**
     * Statuses that unlock chats and buyer details for a supplier.
     *
     * @return array<int, string>
     */
    public static function unlockingValues(): array
    {
        return [self::Trial->value, self::Active->value];
    }

    public function unlocksPlatform(): bool
    {
        return in_array($this, [self::Trial, self::Active], true);
    }

    /**
     * @return 'gray'|'success'|'danger'
     */
    public function color(): string
    {
        return match ($this) {
            self::Active, self::Trial => 'success',
            self::Expired => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
