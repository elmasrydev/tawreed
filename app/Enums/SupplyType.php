<?php

namespace App\Enums;

enum SupplyType: string
{
    case OneTime = 'one_time';
    case Recurring = 'recurring';

    public function label(): string
    {
        return __("enums.supply_type.{$this->value}");
    }
}
