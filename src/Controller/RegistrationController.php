<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, RateLimiterFactoryInterface $registrationLimiter): Response
    {
        // redirige l'utilisateur qui est déjà connecté vers home
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Anti mail-bombing : on consomme un jeton par IP, avant tout persist ou envoi de mail
            $limiter = $registrationLimiter->create($request->getClientIp());
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                // getRetryAfter renvoie une date, pas un nombre de secondes : on calcule le delai restant
                $waitingMinutes = max(1, (int) ceil(($limit->getRetryAfter()->getTimestamp() - time()) / 60));

                $this->addFlash('danger', sprintf(
                    'Trop d\'inscriptions depuis cette connexion. Reessaie dans %d minute%s.',
                    $waitingMinutes,
                    $waitingMinutes > 1 ? 's' : ''
                ));

                // statut 429 : reponse non-200 obligatoire pour que Turbo reaffiche le formulaire
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
            }

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@motoride-connect.fr', 'MotoRide Connect'))
                    ->to((string) $user->getEmail())
                    ->subject('Confirme ton inscription sur MotoRide Connect')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // do anything else you need here, like send an email

            $this->addFlash('success', 'Compte créé ! Vérifie tes emails pour activer ton compte.');

                return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('danger', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Ton email a été vérifié, tu peux maintenant te connecter.');

        return $this->redirectToRoute('app_login');
    }
}
