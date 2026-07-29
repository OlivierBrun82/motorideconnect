<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ModerationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    // Inflige un strike au membre cible (avec un motif). Le service gere le ban auto au seuil.
    #[Route('/user/strike/{id}', name: 'app_admin_strike', methods: ['POST'])]
    public function strike(User $user, Request $request, ModerationManager $moderation) : Response
    {
        // un admin ne peut pas se sanctionner lui-meme
        if ($user === $this->getUser()) {
            return $this->redirectToRoute('app_admin');
        }

        if ($this->isCsrfTokenValid('strike' . $user->getId(), $request->request->get('_token'))) {
            // motif obligatoire : pas de strike si le motif est vide
            $reason = trim((string) $request->request->get('reason'));
            if ($reason !== '') {
                $moderation->addStrike($user, $reason);
            }
        }

        return $this->redirectToRoute('app_admin');
    }

    // Bannit manuellement le membre (bannedDate renseignee)
    #[Route('/user/ban/{id}', name: 'app_admin_ban', methods: ['POST'])]
    public function ban(User $user, Request $request, ModerationManager $moderation) : Response
    {
        // un admin ne peut pas se bannir lui-meme
        if ($user === $this->getUser()) {
            return $this->redirectToRoute('app_admin');
        }

        if ($this->isCsrfTokenValid('ban' . $user->getId(), $request->request->get('_token'))) {
            $moderation->ban($user);
        }

        return $this->redirectToRoute('app_admin');
    }

    // Leve le ban du membre (bannedDate remise a null)
    #[Route('/user/unban/{id}', name: 'app_admin_unban', methods:['POST'])]
    public function unban(User $user, Request $request, ModerationManager $moderation) : Response
    {
        // coherence : pas d'action sur soi-meme
        if ($user === $this->getUser()) {
            return $this->redirectToRoute('app_admin');
        }

        if ($this->isCsrfTokenValid('unban' . $user->getId(), $request->request->get('_token'))) {
            $moderation->unban($user);
        }

        return $this->redirectToRoute('app_admin');
    }
}
