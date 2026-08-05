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

    // Annule la balade et previent les participants ; la raison ne sert qu'au mail
    public function cancel(Ride $ride, string $reason) : void
    {
        $ride->setStatut(RideStatus::Canceled);

        // on persiste avant d'envoyer : si le mailer casse, la balade reste annulee
        $this->em->flush();

        $this->sendCancellationEmails($ride, $reason);
    }

    // Construit et envoie un mail par participant (usage interne uniquement)
    private function sendCancellationEmails(Ride $ride, string $reason) : void
    {
        foreach ($ride->getParticipants() as $participant) {
            // l'organisateur est auto-inscrit : inutile de lui notifier sa propre action
            if ($participant === $ride->getUser()) {
                continue;
            }

            // un TemplatedEmail neuf a chaque tour, sinon les destinataires s'accumulent
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@motoride-connect.fr', 'MotoRide Connect'))
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
