<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __("enums.verification_status.{$this->value}");
    }

    /**
     * The short explanation shown under the status badge in the supplier app.
     */
    public function hint(): string
    {
        return __("enums.verification_status_hint.{$this->value}");
    }

    /**
     * Only verified suppliers may submit quotes.
     */
    public function allowsQuoting(): bool
    {
        return $this === self::Verified;
    }

    /**
     * @return 'gray'|'success'|'warning'|'danger'
     */
    public function color(): string
    {
        return match ($this) {
            self::Verified => 'success',
            self::Pending => 'warning',
            self::Rejected => 'danger',
            self::Suspended => 'gray',
        };
    }
}
