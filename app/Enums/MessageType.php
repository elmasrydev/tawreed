<?php

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case File = 'file';
    case System = 'system';
    case SampleRequest = 'sample_request';

    public function label(): string
    {
        return __("enums.message_type.{$this->value}");
    }
}
