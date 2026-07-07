<?php

namespace App\Enum;

enum DriverLevel: string
{
    case Beginner     = 'beginner';
    case Intermediate = 'intermediate';
    case Confirmed    = 'confirmed';
    case Expert       = 'expert';
    case JoeBarTeam   = 'JoeBarTeam';

    public function label(): string
    {
        return match ($this) {
            self::Beginner     => 'Débutant',
            self::Intermediate => 'Intermédiaire',
            self::Confirmed    => 'Confirmé',
            self::Expert       => 'Expert',
            self::JoeBarTeam   => 'Joe Bar Team',
        };
    }
}