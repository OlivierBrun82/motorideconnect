<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\User;
use App\Enum\RideStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccountDeleter
{
    // Motif envoye aux participants quand une balade est annulee par ce service
    private const CANCELLATION_REASON = "L'organisateur a supprimé son compte.";

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private RideCancellationManager $cancellationManager,
        // Dossier des avatars, meme injection que dans FileUploader
        #[Autowire('%kernel.project_dir%/public/uploads/avatars')]
        private string $avatarsDir,
    ) {
    }

    // Supprime le compte par anonymisation : la ligne User est conservee
    public function delete(User $user) : void
    {
        $this->cancelUpcomingOrganizedRides($user);
        $this->leaveUpcomingRides($user);
        $this->anonymize($user);

        // un seul flush pour la desinscription et l'anonymisation
        $this->em->flush();
    }

    // Annule les balades a venir organisees par ce compte et previent les participants
    private function cancelUpcomingOrganizedRides(User $user) : void
    {
        foreach ($user->getRides() as $ride) {
            if ($this->isUpcoming($ride)) {
                $this->cancellationManager->cancel($ride, self::CANCELLATION_REASON);
            }
        }
    }

    // Retire le compte des balades a venir organisees par d'autres
    private function leaveUpcomingRides(User $user) : void
    {
        foreach ($user->getRidesParticipated() as $ride) {
            // les balades qu'il organise viennent d'etre annulees, isUpcoming() les ecarte
            if (!$this->isUpcoming($ride)) {
                continue;
            }

            $ride->removeParticipant($user);

            // on libere la place : bascule full -> open refaite a la main
            if ($ride->getStatut() === RideStatus::Full) {
                $ride->setStatut(RideStatus::Open);
            }
        }
    }

    // Efface les donnees personnelles ; strikes et likes sont conserves
    private function anonymize(User $user) : void
    {
        $id = $user->getId();

        // l'id est indispensable : email et pseudo sont uniques en base
        $user->setEmail('deleted-' . $id . '@motoride-connect.invalid');
        $user->setPseudo('membre-supprime-' . $id);

        // mot de passe aleatoire jamais communique : le login devient impossible
        $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(32))));

        // c'est ce flag qui bloque la connexion : UserChecker refuse un compte non verifie
        $user->setIsVerified(false);

        $user->setBirthdate(null);
        $user->setAbout(null);
        $user->setPhoneNumber(null);
        $user->setDriverLvl(null);
        $user->setRoles([]);

        $this->removeAvatarFile($user);
    }

    // Supprime le fichier avatar du disque puis vide la colonne
    private function removeAvatarFile(User $user) : void
    {
        $avatar = $user->getAvatar();

        if ($avatar === null) {
            return;
        }

        $path = $this->avatarsDir . '/' . $avatar;

        // is_file obligatoire : unlink sur un fichier absent leve un warning
        if (is_file($path)) {
            unlink($path);
        }

        $user->setAvatar(null);
    }

    // Balade a venir et pas deja annulee
    private function isUpcoming(Ride $ride) : bool
    {
        return $ride->getStatut() !== RideStatus::Canceled
            && $ride->getMeetingDatetime() > new \DateTimeImmutable();
    }
}
