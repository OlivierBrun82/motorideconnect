<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileUpdater
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    public function changePassword(User $user, ?string $currentPassword, ?string $newPassword): bool
    {
        // 1. Si pas de nouveau mot de passe => rien à changer
        if (!$newPassword) {
            return true;
        }

        // 2. Si le mot de passe actuel saisi est invalide => on bloque
        if (!$this->hasher->isPasswordValid($user, $currentPassword)) {
            return false;
        }

        // 3. Si le mot de passe actuel est bon => on hashe et on assigne le nouveau
        $user->setPassword($this->hasher->hashPassword($user, $newPassword));

        // 4. Succès
        return true;
    }

    public function updateProfile(User $user): void
    {
        // on enregistre les changements en base
        $this->em->flush();
    }
}