<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Removed = 'removed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return __("enums.report_status.{$this->value}");
    }

    /**
     * @return 'warning'|'danger'|'gray'
     */
    public function color(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::Removed => 'success',
            self::Dismissed => 'gray',
        };
    }
}
