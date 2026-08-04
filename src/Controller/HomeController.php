<?php

namespace App\Controller;

use App\Repository\RideRepository;
use App\Service\RideDuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class HomeController extends AbstractController
{
    // Taille du fil de l'accueil : /ride reste la liste complete et filtrable
    private const FEED_SIZE = 5;

    #[Route('/', name: 'app_home')]
    public function index(RideRepository $rideRepository, RideDuration $rideDuration): Response
    {
        // Visiteur anonyme : ecran de presentation, aucune requete balade
        if (!$this->getUser()) {
            return $this->render('home/index.html.twig');
        }

        // Sans filtre, findByFilters rend deja les balades A VENIR triees par date croissante
        $rides = array_slice($rideRepository->findByFilters([]), 0, self::FEED_SIZE);

        // Memes donnees que le listing : la carte partagee les attend dans le contexte
        $organizerRideCounts = [];
        // Duree d'affichage : elle n'est pas stockee en base, il faut la calculer
        $durations = [];

        foreach ($rides as $ride) {
            $organizerId = $ride->getUser()->getId();
            $organizerRideCounts[$organizerId] ??= $rideRepository->countByOrganizer($ride->getUser());
            $durations[$ride->getId()] = $rideDuration->format($ride);
        }

        return $this->render('home/index.html.twig', [
            'rides' => $rides,
            'organizerRideCounts' => $organizerRideCounts,
            'durations' => $durations,
        ]);
    }

    // Page statique, atteignable depuis le menu
    #[Route('/mentions-legales', name: 'app_legal')]
    public function legal(): Response
    {
        return $this->render('home/legal.html.twig', []);
    }
}
