<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Production = 'production';
    case Sale = 'sale';
    case Waste = 'waste';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'IN',
            self::Out => 'OUT',
            self::Production => 'PRODUCTION',
            self::Sale => 'SALE',
            self::Waste => 'WASTE',
            self::Transfer => 'TRANSFER',
            self::Adjustment => 'ADJUSTMENT',
        };
    }
}
