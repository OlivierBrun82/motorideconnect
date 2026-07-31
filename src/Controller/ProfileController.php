<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use App\Service\AccountDeleter;
use App\Service\FileUploader;
use App\Service\ProfileUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/profile')]
final class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    public function index(Request $request, ProfileUpdater $updater, FileUploader $fileUploader): Response
    {
        // Récupère l'utilisateur connecté
        $user = $this->getUser();

        // Crée le formulaire pré-rempli avec les infos actuelles de l'user
        $formUser = $this->createForm(ProfileFormType::class, $user);
        // Lit la requête : si le form est soumis, écrit les champs mappés dans $user
        $formUser->handleRequest($request);

        // Le form a été envoyé ET toutes les validations passent
        if ($formUser->isSubmitted() && $formUser->isValid()) {
            
            // Récupère les 2 champs non mappés (gérés à la main)
            $currentPassword = $formUser->get('currentPassword')->getData();
            $newPassword = $formUser->get('plainPassword')->getData();

            // Tente le changement de mot de passe via le service
            if (!$updater->changePassword($user, $currentPassword, $newPassword)) {
                // Mot de passe actuel incorrect : on n'enregistre pas
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            } else {
                // Avatar : si un nouveau fichier est envoyé, on le traite (sinon on garde l'ancien)
                $avatarFile = $formUser->get('avatarFile')->getData();
                if ($avatarFile) {
                    $newFilename = $fileUploader->upload($avatarFile, 'avatars');
                    $user->setAvatar($newFilename);
                }

                // Tout est bon : on enregistre en base
                $updater->updateProfile($user);
                $this->addFlash('success', 'Profil mis à jour.');

                // Redirection après succès (pattern Post/Redirect/Get)
                return $this->redirectToRoute('app_profile');
            }
        }

        // Affiche la page (form vierge, ou réaffiché avec les erreurs)
        return $this->render('profile/index.html.twig', [
            'profileForm' => $formUser,
        ]);
    }
    
    // A declarer AVANT app_profile_id, sinon /{id} capte /profile/delete
    #[Route('/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, AccountDeleter $accountDeleter, UserPasswordHasherInterface $hasher, Security $security) : Response
    {
        // uniquement son propre compte : la moderation dispose deja du ban
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            return $this->redirectToRoute('app_profile');
        }

        // action irreversible : on exige le mot de passe, le CSRF seul ne suffit pas
        $password = (string) $request->request->get('password');

        if (!$hasher->isPasswordValid($user, $password)) {
            $this->addFlash('danger', 'Mot de passe incorrect, le compte n\'a pas été supprimé.');

            return $this->redirectToRoute('app_profile');
        }

        // annule les balades a venir, desinscrit, puis anonymise
        $accountDeleter->delete($user);

        // false = pas de validation du CSRF de logout, le notre a deja ete valide
        $security->logout(false);

        // le flash APRES le logout : l'invalidation de session effacerait un flash pose avant
        $this->addFlash('success', 'Ton compte a été supprimé. Tes données personnelles ont été effacées.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/{id}', name:'app_profile_id')]
    public function show(User $user) : Response
    {
        return $this->render('profile/show.html.twig', [
            'user' => $user,
        ]);
    }
}
