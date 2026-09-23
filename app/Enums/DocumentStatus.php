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
}
