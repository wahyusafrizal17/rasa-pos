<?php

namespace App\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Nominal = 'nominal';
    case Bogo = 'bogo';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Nominal => 'Nominal',
            self::Bogo => 'Buy X Get Y',
        };
    }
}
