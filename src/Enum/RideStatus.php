<?php

namespace App\Enum;

enum RideStatus: string
{
    case Open      = 'open';
    case Full      = 'full';
    case Canceled  = 'canceled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Open      => 'Ouverte',
            self::Full      => 'Complète',
            self::Canceled  => 'Annulée',
            self::Completed => 'Terminée',
        };
    }
}