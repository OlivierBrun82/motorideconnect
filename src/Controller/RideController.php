<?php

namespace App\Controller;

use App\Form\RideType;
use App\Entity\Ride;
use App\Entity\User;
use App\Enum\RideStatus;
use App\Exception\RideRegistrationException;
use App\Form\RideFilterType;
use App\Repository\MotorcycleRepository;
use App\Repository\RideRepository;
use App\Service\RideRegistrationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/ride')]
final class RideController extends AbstractController
{

    #[Route('/', name: 'app_ride', methods: ['GET'])]
    public function index(Request $request, RideRepository $rideRepository): Response
    {
        $form = $this->createForm(RideFilterType::class);
        $form->handleRequest($request);

        $rides = $rideRepository->findByFilters($form->getData() ?? []);

        return $this->render('ride/index.html.twig', [
            'form' => $form,
            'rides' => $rides,
        ]);
    }

    #[Route('/new', name: 'app_ride_new', methods:['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        // on stocke dans la var user l'utilisateur connecté (jamais null grâce à IsGranted)
        $user = $this->getUser();
        // on crée une nouvelle instance de Ride (vide, remplie par le formulaire)
        $ride = new Ride();
        // on relie le form à cet objet, en lui passant le user connecté
        // (l'option 'owner' sert au query_builder du champ moto : ne lister que SES motos)
        $form = $this->createForm(RideType::class, $ride, ['owner' => $user]);
        // lit la requête et injecte les données dans $ride
        $form->handleRequest($request);

        // form envoyé ET valide
        if ($form->isSubmitted() && $form->isValid()) {

            // l'organisateur = l'utilisateur connecté
            $ride->setUser($user);

            // toute nouvelle balade naît "Ouverte"
            $ride->setStatut(RideStatus::Open);

            // auto-inscription de l'organisateur : il occupe une place d'emblée
            $ride->addParticipant($user);

            // moto choisie (champ non mappé) : si renseignée, on l'ajoute à la balade
            $motorcycle = $form->get('motorcycle')->getData();
            if ($motorcycle) {
                $ride->addMotorcycle($motorcycle);
            }

            // nouvel objet => persist (Doctrine le suit) puis flush (INSERT en base)
            $em->persist($ride);
            $em->flush();

            $this->addFlash('success', 'Balade créée.');

            // Post/Redirect/Get : on redirige vers la fiche de la balade créée
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        // premier affichage (GET) ou POST invalide => on (ré)affiche le formulaire
        return $this->render('ride/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/show/{id}', name: 'app_ride_show', methods:['GET'])]
        public function show(Ride $ride) : Response
        {
            return $this->render('ride/show.html.twig', [
                'ride' => $ride
            ]);

        }
    
    #[Route('/edit/{id}', name: 'app_ride_edit', methods:['GET', 'POST'])]
        public function edit(Ride $ride, Request $request, EntityManagerInterface $em) : Response
        {
            if ($ride->getUser() !== $this->getUser()) {
                throw $this->createAccessDeniedException("Tu n'est pas le créateur de cette balade");
            }

            // owner = le créateur de la balade (= user connecté ici, grâce au garde-fou)
            $form = $this->createForm(RideType::class, $ride, ['owner' => $ride->getUser()]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // moto choisie (champ non mappé) : si renseignée, on l'ajoute
                $motorcycle = $form->get('motorcycle')->getData();
                if ($motorcycle) {
                    $ride->addMotorcycle($motorcycle);
                }

                $em->flush();
                $this->addFlash('success', 'Balade modifiée.');
                return $this->redirectToRoute('app_ride_show', [
                    'id' => $ride->getId()
                ]);
            }

            return $this->render('ride/edit.html.twig', [
                'form' => $form,
                'ride' => $ride
            ]);
        }
    
    #[Route('/delete/{id}', name: 'app_ride_delete', methods:['POST'])]
    public function delete(Ride $ride, Request $request, EntityManagerInterface $em) : Response
    {
        // vérification que c'est l'utilisateur de cette balade qui demande le delete
        if ($ride->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Tu n'est pas le createur de cette balade");
        }

        // vérification du token et supression si token valide
        if ($this->isCsrfTokenValid('delete' . $ride->getId(), $request->request->get('_token'))) {
            foreach ($ride->getComments() as $comment) {
                $em->remove($comment);
            }
            $em->remove($ride);
            $em->flush();
            
            $this->addFlash('success', 'Balade supprimée.');

        }

        return $this->redirectToRoute('app_ride');
    }

    #[Route('/participate/{id}', name: 'app_ride_participate', methods:['POST'])]
    public function participate(Ride $ride, Request $request, RideRegistrationManager $manager, MotorcycleRepository $motorcycleRepository) : Response
    {
        // On récupère le token et vérifie sa validité, si invalide on redirige vers la fiche
        if (!$this->isCsrfTokenValid('participate' . $ride->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        // On vérifie que la moto sélectionnée appartient bien à l'user
        $user = $this->getUser();
        $moto = null;

        $motoId = $request->request->get('motorcycle');
        if ($motoId) {
            $moto = $motorcycleRepository->find($motoId);

            if ($moto === null || $moto->getUser() !== $user) {
                throw $this->createAccessDeniedException("Cette moto ne t'appartient pas, petit coquin!");
            }
        }

        // on appelle le service
        try {
            $manager->join($ride, $user, $moto);
            $this->addFlash('success', 'Inscription confirmée !');
        } catch (RideRegistrationException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }

    #[Route('/leave/{id}', name:'app_ride_leave', methods:['POST'])]
    public function leave(Ride $ride, Request $request, RideRegistrationManager $manager) : Response
    {
        // Si pas de Token valid on redirige vers le show
        if (!$this->isCsrfTokenValid('leave' .$ride->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
        }

        // sinon on retire l'user de la balade
        try {
            $manager->leave($ride, $this->getUser());
            $this->addFlash('success', 'Tu es désinscrit de la balade.');
        } catch (RideRegistrationException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }

    #[Route('/cancel/{id}', name:'app_ride_cancel', methods:['POST'])]
    public function cancel(Ride $ride, Request $request, EntityManagerInterface $em) : Response
    {
        if ($ride->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Tu n'es pas le créateur de cette balade.");
        }

        if ($this->isCsrfTokenValid('cancel' . $ride->getId(), $request->request->get('_token'))) {
            $ride->setStatut(RideStatus::Canceled);
            $em->flush();
            $this->addFlash('success', "Balade annulée.");
        }

        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }

    #[Route('/like/{id}', name:'app_ride_like', methods:['POST'])]
    public function like(Ride $ride, Request $request, EntityManagerInterface $em) : Response
    {
        if ($this->isCsrfTokenValid('like' . $ride->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();

            // Seul un participant de la balade peut la liker
            if (!$ride->getParticipants()->contains($user)) {
                $this->addFlash('danger', 'Tu dois participer à la balade pour pouvoir la liker.');
                return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_ride'));
            }

            if ($ride->getLikedBy()->contains($user)) {
                $ride->removeLikedBy($user);
            } else {
                $ride->addLikedBy($user);
            }

            $em->flush();
        }

        // retour sur la page d'origine (show ou listing), repli sur le listing
        $referer = $request->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('app_ride'));
    }
}
