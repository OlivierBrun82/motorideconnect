<?php

namespace App\Tests;

use App\Entity\Ride;
use App\Service\RideDuration;
use PHPUnit\Framework\TestCase;

class RideDurationTest extends TestCase
{
    // Fabrique une balade portant seulement les heures utiles au calcul
    private function makeRide(string $start, ?string $end = null) : Ride
    {
        $ride = new Ride();
        $ride->setStartTime(new \DateTimeImmutable($start));

        if ($end !== null) {
            $ride->setEndTime(new \DateTimeImmutable($end));
        }

        return $ride;
    }

    // Cas nominal : 09:00 a 12:30 fait 3h30, soit 210 minutes
    public function testMinutesOnStandardRide() : void
    {
        $duration = new RideDuration;

        $this->assertSame(210, $duration->minutes($this->makeRide('09:00', '12:30')));
    }

    // Sans heure d'arrivee, aucune duree n'est calculable
    public function testMinutesWithoutEndTime() : void
    {
        $duration = new RideDuration();

        $this->assertNull($duration->minutes($this->makeRide('09:00')));
    }

    // Une duree inconnue n'entre dans aucune tranche
    public function testMatchesNothingWithoutEndTime() : void
    {
        $duration = new RideDuration();
        $ride = $this->makeRide('09:00');

        $this->assertFalse($duration->matches($ride, RideDuration::SHORT));
        $this->assertFalse($duration->matches($ride, RideDuration::MEDIUM));
        $this->assertFalse($duration->matches($ride, RideDuration::LONG));
    }

    // Balade nocturne : l'arrivee tombe le lendemain, la duree reste positive
    public function testMinutesOnOvernightRide() : void
    {
        $duration = new RideDuration();

        $this->assertSame(180, $duration->minutes($this->makeRide('22:00', '01:00')));
    }
}