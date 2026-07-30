<?php

namespace App\Service;

use App\Entity\Ride;

/**
 * Calcul de la duree estimee d'une balade (endTime - StartTime).
 *
 * La duree n'est stockee nulle part (entites figees) : elle est toujours
 * recalculee. Sert au filtre par duree de /ride et a l'affichage sur ride/show.
 */
final class RideDuration
{
    public const SHORT = 'short';
    public const MEDIUM = 'medium';
    public const LONG = 'long';

    // Bornes en minutes : court < 120 <= moyen <= 240 < long.
    // Privees : personne d'autre n'a besoin de connaitre les seuils.
    private const MEDIUM_MIN = 120;
    private const MEDIUM_MAX = 240;

    // Format attendu par l'option "choices" d'un ChoiceType : libelle affiche => valeur.
    // Publique : consommee par RideFilterType.
    public const CHOICES = [
        'Moins de 2h' => self::SHORT,
        'De 2h à 4h' => self::MEDIUM,
        'Plus de 4h' => self::LONG,
    ];

    /**
     * Duree de la balade en minutes, ou null si elle est inconnue.
     */
    public function minutes(Ride $ride): ?int
    {
        $start = $ride->getStartTime();
        $end = $ride->getEndTime();

        // endTime est nullable en base : sans heure d'arrivee, aucune duree calculable
        if ($start === null || $end === null) {
            return null;
        }

        $minutes = $this->minutesOfDay($end) - $this->minutesOfDay($start);

        // Balade nocturne (22h -> 01h) : l'arrivee est le lendemain, donc +24h
        if ($minutes < 0) {
            $minutes += 1440;
        }

        return $minutes;
    }

    /**
     * La balade tombe-t-elle dans la tranche demandee (self::SHORT / MEDIUM / LONG) ?
     */
    public function matches(Ride $ride, string $bucket): bool
    {
        $minutes = $this->minutes($ride);

        // Duree inconnue : la balade ne peut etre rangee dans aucune tranche,
        // on ne peut pas affirmer qu'elle est courte
        if ($minutes === null) {
            return false;
        }

        return match ($bucket) {
            self::SHORT => $minutes < self::MEDIUM_MIN,
            self::MEDIUM => $minutes >= self::MEDIUM_MIN && $minutes <= self::MEDIUM_MAX,
            self::LONG => $minutes > self::MEDIUM_MAX,
            // Tranche inconnue (URL trafiquee) : on ne filtre rien plutot que
            // de renvoyer une liste vide inexplicable
            default => true,
        };
    }

    /**
     * Nombre de minutes ecoulees depuis minuit.
     *
     * On ne lit que les heures et les minutes : les colonnes sont des TIME sans date,
     * Doctrine les hydrate avec une date arbitraire sur laquelle il ne faut pas compter.
     */
    private function minutesOfDay(\DateTimeImmutable $time): int
    {
        return (int) $time->format('H') * 60 + (int) $time->format('i');
    }
}
