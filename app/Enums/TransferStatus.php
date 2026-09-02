<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case Approved = 'approved';
    case Shipped = 'shipped';
    case Received = 'received';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Shipped => 'Shipped',
            self::Received => 'Received',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Requested => 'orange',
            self::Approved => 'blue',
            self::Shipped => 'indigo',
            self::Received, self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}
