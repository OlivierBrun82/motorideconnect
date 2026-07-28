<?php

namespace App\Service;

use App\Entity\Strikes;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ModerationManager
{
    // le seuil de strikes qui déclanche le ban automatique
    private const STRIKE_THRESOLD = 3;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function addStrike(User $target, string $reason) : void
    {
        // création du strike sur la cible
        $strike = new Strikes();
        $strike->setUser($target);
        $strike->setReason($reason);
        $strike->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($strike);

        // ban automatique si ce nouveau strike atteint le seuil (ban() fait deja un flush)
        if ($target->getStrikes()->count() + 1 >= self::STRIKE_THRESOLD) {
            $this->ban($target);
        }

        // flush dans tous les cas, sinon les strikes sous le seuil ne sont jamais sauvegardes
        $this->em->flush();
    }
    
    public function ban(User $target) : void
    {
        $target->setBannedDate(new \DateTime());
        $this->em->flush();
    }

    public function unban(User $target) : void
    {
        $target->setBannedDate(null);
        $this->em->flush();
    }
}