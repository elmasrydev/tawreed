<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("enums.document_status.{$this->value}");
    }

    /**
     * @return 'gray'|'success'|'danger'
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Accepted => 'success',
            self::Rejected => 'danger',
        };
    }
}
