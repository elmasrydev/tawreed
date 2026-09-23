<?php

namespace App\Enums;

enum UserRole: string
{
    case Buyer = 'buyer';
    case Supplier = 'supplier';
    case Admin = 'admin';

    public function label(): string
    {
        return __("enums.user_role.{$this->value}");
    }

    /**
     * The route the user lands on after authenticating.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Buyer => 'buyer.home',
            self::Supplier => 'supplier.dashboard',
            self::Admin => 'filament.admin.pages.dashboard',
        };
    }
}
