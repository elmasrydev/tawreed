<?php

namespace App\Enums;

enum RfqStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Awarded = 'awarded';
    case Closed = 'closed';
    case Expired = 'expired';
    case Removed = 'removed';

    public function label(): string
    {
        return __("enums.rfq_status.{$this->value}");
    }

    /**
     * Suppliers may only quote on requests that are still open.
     */
    public function acceptsQuotes(): bool
    {
        return $this === self::Open;
    }

    /**
     * Statuses that appear in the public supplier feed.
     *
     * @return array<int, self>
     */
    public static function browsable(): array
    {
        return [self::Open];
    }

    /**
     * @return 'gray'|'success'|'warning'|'info'|'danger'
     */
    public function color(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::Awarded => 'success',
            self::Draft, self::Closed, self::Expired => 'gray',
            self::Removed => 'danger',
        };
    }
}
