<?php

namespace App\Service;

use App\Entity\Ride;
use App\Enum\RideStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class RideCancellationManager
{
    public function __construct(private EntityManagerInterface $em, private MailerInterface $mailer)
    {
    }

    // Annule la balade et previent les participants
    // La raison n'est jamais stockee (entites figees) : elle ne sert qu'au mail
    public function cancel(Ride $ride, string $reason) : void
    {
        $ride->setStatut(RideStatus::Canceled);

        // on persiste avant d'envoyer : si le mailer casse, la balade reste annulee
        // l'inverse previendrait les participants d'une annulation non enregistree
        $this->em->flush();

        $this->sendCancellationEmails($ride, $reason);
    }

    // Construit et envoie un mail par participant (usage interne uniquement)
    private function sendCancellationEmails(Ride $ride, string $reason) : void
    {
        foreach ($ride->getParticipants() as $participant) {
            // l'organisateur est auto-inscrit comme participant depuis la creation :
            // inutile de lui notifier sa propre action
            if ($participant === $ride->getUser()) {
                continue;
            }

            // un TemplatedEmail neuf a chaque tour : reutiliser l'objet
            // accumulerait les destinataires d'un envoi sur l'autre
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@motoride-connect.fr', 'MotoRide Connect'))
                ->to((string) $participant->getEmail())
                ->subject('La balade ' . $ride->getName() . ' a été annulée')
                ->htmlTemplate('ride/cancel_email.html.twig')
                ->context([
                    'ride' => $ride,
                    'reason' => $reason,
                ]);

            $this->mailer->send($email);
        }
    }
}
