<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case OutletManager = 'outlet_manager';
    case Cashier = 'cashier';
    case Captain = 'captain';
    case Kitchen = 'kitchen';
    case Bar = 'bar';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::OutletManager => 'Outlet Manager',
            self::Cashier => 'Cashier',
            self::Captain => 'Captain Order',
            self::Kitchen => 'Kitchen Checker',
            self::Bar => 'Bar Checker',
        };
    }
}
