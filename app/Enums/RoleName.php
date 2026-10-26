<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case OutletManager = 'outlet_manager';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::OutletManager => 'Outlet Manager',
            self::Cashier => 'Cashier',
        };
    }
}
