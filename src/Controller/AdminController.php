<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Entity\User;
use App\Repository\BrandRepository;
use App\Repository\UserRepository;
use App\Service\ModerationManager;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(UserRepository $userRepository, BrandRepository $brandRepository, Request $request): Response
    {
        // Marque a corriger, choisie dans le select (null si aucune selection).
        $selectedBrand = null;
        $brandId = $request->query->getInt('brand');
        if ($brandId > 0) {
            $selectedBrand = $brandRepository->find($brandId);
        }

        return $this->render('admin/index.html.twig', [
            'users' => $userRepository->findAll(),
            'brands' => $brandRepository->findBy([], ['name' => 'ASC']),
            'selectedBrand' => $selectedBrand,
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

    // Ajoute une marque absente du catalogue importe depuis data/brands.csv
    #[Route('/brand/new', name: 'app_admin_brand_new', methods: ['POST'])]
    public function brandNew(Request $request, EntityManagerInterface $em, BrandRepository $brandRepository) : Response
    {
        if ($this->isCsrfTokenValid('brand_new', $request->request->get('_token'))) {
            $name = trim((string) $request->request->get('name'));
            $error = $this->validateBrandName($name, $brandRepository);

            if ($error !== null) {
                $this->addFlash('danger', $error);
            } else {
                // Objet neuf : persist puis flush (INSERT)
                $brand = new Brand();
                $brand->setName($name);
                $em->persist($brand);
                $em->flush();
                $this->addFlash('success', 'Marque ajoutée.');
            }
        }

        return $this->redirectToRoute('app_admin');
    }

    // Corrige le nom d'une marque existante.
    #[Route('/brand/{id}/edit', name: 'app_admin_brand_edit', methods: ['POST'])]
    public function brandEdit(Brand $brand, Request $request, EntityManagerInterface $em, BrandRepository $brandRepository) : Response
    {
        if ($this->isCsrfTokenValid('brand_edit' . $brand->getId(), $request->request->get('_token'))) {
            $name = trim((string) $request->request->get('name'));
            $error = $this->validateBrandName($name, $brandRepository, $brand);

            if ($error !== null) {
                $this->addFlash('danger', $error);
            } else {
                // Objet deja suivi par Doctrine : pas de persist, juste flush (UPDATE)
                $brand->setName($name);
                $em->flush();
                $this->addFlash('success', 'Marque modifiée.');
            }
        }

        return $this->redirectToRoute('app_admin');
    }

    // Verifie un nom de marque : retourne le message d'erreur, ou null si valide.
    private function validateBrandName(string $name, BrandRepository $brandRepository, ?Brand $current = null) : ?string
    {
        if ($name === '') {
            return 'Le nom de la marque est obligatoire.';
        }

        // mb_strlen et pas strlen : la colonne fait 100 caracteres, pas 100 octets
        if (mb_strlen($name) > 100) {
            return 'Le nom de la marque ne doit pas dépasser 100 caractères.';
        }

        // Doctrine ne rend qu'une instance par ligne : la comparaison d'objets suffit.
        $existing = $brandRepository->findOneByNameInsensitive($name);
        if ($existing !== null && $existing !== $current) {
            return sprintf('La marque « %s » existe déjà.', $existing->getName());
        }

        return null;
    }
}
