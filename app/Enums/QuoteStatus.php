<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Pending = 'pending';
    case Shortlisted = 'shortlisted';
    case Selected = 'selected';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';

    public function label(): string
    {
        return __("enums.quote_status.{$this->value}");
    }

    /**
     * The buyer can still shortlist, reject or select a quote in these states.
     */
    public function isActionable(): bool
    {
        return in_array($this, [self::Pending, self::Shortlisted], true);
    }

    /**
     * @return 'gray'|'success'|'warning'|'danger'
     */
    public function color(): string
    {
        return match ($this) {
            self::Selected => 'success',
            self::Shortlisted => 'warning',
            self::Rejected, self::Withdrawn => 'danger',
            self::Pending => 'info',
            self::Expired => 'gray',
        };
    }
}
