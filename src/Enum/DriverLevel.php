<?php

namespace App\Enum;

enum DriverLevel: string
{
    case Beginner     = 'Débutant';
    case Intermediate = 'Intermédiaire';
    case Confirmed    = 'confirmé';
    case Expert       = 'expert';
    case JoeBarTeam   = 'JoeBarTeam';
}