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

    // Supprime le compte par anonymisation : la ligne User est conservee,
    // seules les donnees personnelles sont effacees.
    // Les entites sont figees et tous les ManyToOne vers User sont en nullable:false,
    // donc un remove() reel violerait les contraintes et detruirait le contenu d'autrui
    // (les commentaires des autres membres sur les balades de ce compte).
    public function delete(User $user) : void
    {
        $this->cancelUpcomingOrganizedRides($user);
        $this->leaveUpcomingRides($user);
        $this->anonymize($user);

        // un seul flush pour la desinscription et l'anonymisation
        // (l'annulation des balades flushe deja de son cote, a chaque appel)
        $this->em->flush();
    }

    // Annule les balades a venir organisees par ce compte et previent les participants.
    // En premier : les mails partent avec les donnees encore intactes.
    private function cancelUpcomingOrganizedRides(User $user) : void
    {
        foreach ($user->getRides() as $ride) {
            if ($this->isUpcoming($ride)) {
                $this->cancellationManager->cancel($ride, self::CANCELLATION_REASON);
            }
        }
    }

    // Retire le compte des balades a venir organisees par d'autres :
    // anonymise, il occuperait sinon une place que personne ne peut liberer
    private function leaveUpcomingRides(User $user) : void
    {
        foreach ($user->getRidesParticipated() as $ride) {
            // les balades qu'il organise sont aussi dans cette collection (auto-inscription),
            // mais elles viennent d'etre annulees donc isUpcoming() les ecarte deja
            if (!$this->isUpcoming($ride)) {
                continue;
            }

            $ride->removeParticipant($user);

            // on libere la place : RideRegistrationManager::leave() n'est pas utilisable ici
            // (il refuse l'organisateur), donc la bascule full -> open est refaite a la main
            if ($ride->getStatut() === RideStatus::Full) {
                $ride->setStatut(RideStatus::Open);
            }
        }
    }

    // Efface les donnees personnelles. Les strikes et les likes sont conserves :
    // trace de moderation d'un cote, compteurs d'anciennes balades de l'autre.
    private function anonymize(User $user) : void
    {
        $id = $user->getId();

        // l'id est indispensable : email et pseudo sont uniques en base,
        // sans lui la deuxieme suppression de compte violerait la contrainte.
        // .invalid est un TLD reserve (RFC 2606) : aucun mail ne peut y partir
        $user->setEmail('deleted-' . $id . '@motoride-connect.invalid');
        $user->setPseudo('membre-supprime-' . $id);

        // mot de passe aleatoire jamais communique : le login devient impossible
        $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(32))));

        // c'est ce flag qui bloque reellement la connexion : UserChecker refuse
        // tout compte non verifie, avant meme de regarder le ban
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

        // is_file obligatoire : unlink sur un fichier absent leve un warning,
        // que Symfony transforme en exception en environnement dev
        if (is_file($path)) {
            unlink($path);
        }

        $user->setAvatar(null);
    }

    // Balade a venir et pas deja annulee : meme condition que le garde-fou de RideController::cancel()
    private function isUpcoming(Ride $ride) : bool
    {
        return $ride->getStatut() !== RideStatus::Canceled
            && $ride->getMeetingDatetime() > new \DateTimeImmutable();
    }
}
