<?php

namespace App\Enums;

enum VatMode: string
{
    case Included = 'included';
    case Excluded = 'excluded';

    public function label(): string
    {
        return __("enums.vat_mode.{$this->value}");
    }
}
