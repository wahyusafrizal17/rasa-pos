<?php

namespace App\Enums;

enum OrderType: string
{
    case DineIn = 'dine_in';
    case TakeAway = 'take_away';
    case Pickup = 'pickup';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::DineIn => 'Dine In',
            self::TakeAway => 'Take Away',
            self::Pickup => 'Pickup',
            self::Online => 'Online',
        };
    }
}
