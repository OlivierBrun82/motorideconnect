<?php

namespace App\Enum;

enum RideRhythm: string
{
    case Touristique = 'touristique';
    case Calm        = 'calm';
    case Dynamic     = 'dynamic';
    case Sport       = 'sport';

    public function label(): string
    {
        return match ($this) {
            self::Touristique => 'Touristique',
            self::Calm        => 'Cool',
            self::Dynamic     => 'Dynamique',
            self::Sport       => 'Sportif',
        };
    }
}