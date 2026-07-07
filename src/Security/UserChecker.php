<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    // Vérifie que l'utilisateur à bien fais la vérification par email
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Ton compte n\'est pas encore activé. Vérifie tes emails pour cliquer sur le lien de confirmation.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}