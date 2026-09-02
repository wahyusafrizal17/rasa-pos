<?php

namespace App\Enums;

enum ProductType: string
{
    case Raw = 'raw';
    case SemiFinished = 'semi_finished';
    case Finished = 'finished';
    case Package = 'package';

    public function label(): string
    {
        return match ($this) {
            self::Raw => 'Raw Material',
            self::SemiFinished => 'Semi Finished',
            self::Finished => 'Finished Goods',
            self::Package => 'Package / Bundle',
        };
    }
}
