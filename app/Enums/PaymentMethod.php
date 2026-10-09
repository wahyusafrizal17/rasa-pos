<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Edc = 'edc';
    case Qris = 'qris';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Edc => 'EDC',
            self::Qris => 'QRIS',
            self::Transfer => 'Transfer',
        };
    }
}
