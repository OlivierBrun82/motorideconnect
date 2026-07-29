<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BannedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // on ne traite que la requête principale (pas les sous-requêtes)
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        // pas connecté, ou pas un User de l'app => rien à faire
        if (!$user instanceof User || $user->getBannedDate() === null) {
            return;
        }

        // membre banni mais encore connecté : on coupe la session
        $this->tokenStorage->setToken(null);
        $session = $event->getRequest()->getSession();
        $session->invalidate();
        $session->getFlashBag()->add('error', 'Ton compte a été banni.');

        // redirection vers le login
        $event->setResponse(
            new RedirectResponse($this->urlGenerator->generate('app_login'))
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }
}