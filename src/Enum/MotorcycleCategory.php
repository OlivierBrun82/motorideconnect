<?php

namespace App\Enum;

enum MotorcycleCategory: string
{
    case Roadster    = 'roadster';
    case Trail       = 'trail';
    case Custom      = 'custom';
    case Sport       = 'sport';
    case SportGT     = 'sport-gt';
    case Supermotard = 'supermotard';
    case Sidecar     = 'sidecar';

    public function label(): string
    {
        return match ($this) {
            self::Roadster    => 'Roadster',
            self::Trail       => 'Trail',
            self::Custom      => 'Custom',
            self::Sport       => 'Sport',
            self::SportGT     => 'Sport-GT',
            self::Supermotard => 'Supermotard',
            self::Sidecar     => 'Sidecar',
        };
    }
}
