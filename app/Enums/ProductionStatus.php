<?php

namespace App\Enums;

enum ProductionStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case InProduction = 'in_production';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Planned => 'Planned',
            self::InProduction => 'In Production',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Planned => 'blue',
            self::InProduction => 'orange',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}
