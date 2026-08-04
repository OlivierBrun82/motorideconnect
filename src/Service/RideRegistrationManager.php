<?php

namespace App\Service;

use App\Entity\Motorcycle;
use App\Entity\Ride;
use App\Entity\User;
use App\Enum\RideStatus;
use App\Exception\RideRegistrationException;
use Doctrine\ORM\EntityManagerInterface;

class RideRegistrationManager
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function join(Ride $ride, User $user, ?Motorcycle $moto): void
    {
        // L'utilisateur est-il déjà inscrit à cette balade ?
        if ($ride->getParticipants()->contains($user)) {
           throw new RideRegistrationException('Tu es déjà inscrit à cette balade');
        }

        // La balade est elle annulée ?
        if ($ride->getStatut() === RideStatus::Canceled) {
            throw new RideRegistrationException('Tu ne peux pas rejoindre cette balade, elle est annulée.');
        }

        // La balade est elle passée ?
        if ($ride->getMeetingDatetime() < new \DateTimeImmutable()) {
            throw new RideRegistrationException('La balade est déjà passée.');
        }

        // La balade est complète ?
        if ($ride->getStatut() === RideStatus::Full || $ride->getParticipants()->count() >= $ride->getCapacity()) {
           throw new RideRegistrationException('La balade est complète.');
        }

        // Si tout est bon on inscrit le participant
        $ride->addParticipant($user);

        // Si une moto est renseigné, on l'ajoute à la balade
        if ($moto !== null) {
            $ride->addMotorcycle($moto);
        }

        // Si la capacité est atteinte, bascule la balade en Complète
        if ($ride->getParticipants()->count() >= $ride->getCapacity()) {
            $ride->setStatut(RideStatus::Full);
        }

        $this->em->flush();
    }

    public function leave(Ride $ride, User $user) : void
    {
        // L'organisateur ne peut se désinscrire d'une balade, il peut la supprimer au besoin.
        if ($ride->getUser() === $user) {
            throw new RideRegistrationException("En tant qu'organisateur, tu ne peux pas te désinscrire. Supprime la balade si besoin.");
        }

        // On ne peut pas désinscrire quelqu'un qui n'est pas inscrit.
        if (!$ride->getParticipants()->contains($user)) {
            throw new RideRegistrationException("Tu n'es pas inscrit à cette balade.");
        }

        // Désinscription
        $ride->removeParticipant($user);

        // Si une place se libère, change le statut d'une balade en ouverte.
        if ($ride->getStatut() === RideStatus::Full) {
            $ride->setStatut(RideStatus::Open);
        }

        $this->em->flush();
    }

    // Apres une edition de capacite, le statut ne correspond plus au nombre d'inscrits.
    // Pas de flush ici : l'appelant ecrit deja, on evite une seconde ecriture.
    public function refreshStatus(Ride $ride) : void
    {
        // Une balade annulee ou terminee garde son statut, la capacite n'y change rien.
        if ($ride->getStatut() === RideStatus::Canceled || $ride->getStatut() === RideStatus::Completed) {
            return;
        }

        if ($ride->getParticipants()->count() >= $ride->getCapacity()) {
            $ride->setStatut(RideStatus::Full);
        } else {
            $ride->setStatut(RideStatus::Open);
        }
    }
}