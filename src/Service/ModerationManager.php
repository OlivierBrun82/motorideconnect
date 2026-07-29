<?php

namespace App\Service;

use App\Entity\Strikes;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ModerationManager
{
    // le seuil de strikes qui déclanche le ban automatique
    private const STRIKE_THRESOLD = 3;

    public function __construct(private EntityManagerInterface $em, private MailerInterface $mailer)
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

        // notification email du strike a la cible (motif + total de strikes)
        $this->sendStrikeEmail($target, $reason);
    }

    // Construit et envoie l'email d'avertissement (usage interne uniquement)
    private function sendStrikeEmail(User $target, string $reason) : void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@motoride-connect.fr', 'MotoRide Connect'))
            ->to((string) $target->getEmail())
            ->subject('Tu as reçu un avertissement sur MotoRide Connect')
            ->htmlTemplate('moderation/strike_email.html.twig')
            ->context([
                'reason' => $reason,
                'strikeCount' => $target->getStrikes()->count(),
            ]);

        $this->mailer->send($email);
    }
    
    // Bannit le membre (bannedDate = maintenant) et le notifie par email
    public function ban(User $target) : void
    {
        $target->setBannedDate(new \DateTime());
        $this->em->flush();

        // notification email du bannissement
        $this->sendBanEmail($target);
    }

    // Construit et envoie l'email de bannissement (usage interne uniquement)
    private function sendBanEmail(User $target) : void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@motoride-connect.fr', 'Motoride-Connect'))
            ->to((string) $target->getEmail())
            ->subject('Ton compte Motoride Connect a été banni')
            ->htmlTemplate('moderation/ban_email.html.twig');
        
        $this->mailer->send($email);
    }

    // Leve le ban du membre (bannedDate remise a null) et le notifie par email
    public function unban(User $target) : void
    {
        $target->setBannedDate(null);
        $this->em->flush();

        // notification email de la levee du ban
        $this->sendUnbanEmail($target);
    }

    // Construit et envoie l'email de levee de ban (usage interne uniquement)
    private function sendUnbanEmail(User $target) : void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@motoride-connect.fr', 'MotoRide Connect'))
            ->to((string) $target->getEmail())
            ->subject('Ton compte MotoRide Connect a été réactivé')
            ->htmlTemplate('moderation/unban_email.html.twig');

        $this->mailer->send($email);
    }
}