<?php

namespace App\Enums;

enum WasteReason: string
{
    case Damaged = 'damaged';
    case Expired = 'expired';
    case Spoiled = 'spoiled';
    case ProductionWaste = 'production_waste';
    case WrongPreparation = 'wrong_preparation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Damaged => 'Damaged',
            self::Expired => 'Expired',
            self::Spoiled => 'Spoiled',
            self::ProductionWaste => 'Production Waste',
            self::WrongPreparation => 'Wrong Preparation',
            self::Other => 'Other',
        };
    }
}
