<?php

namespace App\Controller;

use App\Service\FileUploader;
use App\Entity\User;
use App\Entity\Motorcycle;
use App\Form\MotorcycleType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/motorcycle')]
final class MotorcycleController extends AbstractController
{
    // Le garage : liste uniquement les motos de l'utilisateur connecté
    #[Route('/', name: 'app_motorcycle', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // On lit directement la collection de l'user connecté (ses motos)
        return $this->render('motorcycle/index.html.twig', [
            'motorcycles' => $user->getMotorcycles(),
        ]);
    }

    #[Route('/new', name: 'app_motorcycle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        // Crée un objet moto vide, que le formulaire va remplir
        $motorcycle = new Motorcycle();
        $form = $this->createForm(MotorcycleType::class, $motorcycle);
        // Lit la requête : si le form est soumis, écrit les champs dans $motorcycle
        $form->handleRequest($request);

        // Form envoyé ET valide
        if ($form->isSubmitted() && $form->isValid()) {

            // Le propriétaire = l'user connecté (jamais choisi dans le form, sécurité)
            $motorcycle->setUser($this->getUser());

            // Récupère le fichier envoyé (champ non mappé du form)
            $photoFile = $form->get('photoFile')->getData();

            // S'il y a un fichier, on le traite et on stocke son nom en base
            if ($photoFile) {
                $newFilename = $fileUploader->upload($photoFile, 'motorcycles');
                $motorcycle->setPhoto($newFilename);
            }

            // Nouvel objet => persist (Doctrine commence à le suivre) PUIS flush (INSERT)
            $em->persist($motorcycle);
            $em->flush();

            $this->addFlash('success', 'Moto ajoutée.');

            // Redirection vers le garage après ajout (Post/Redirect/Get)
            return $this->redirectToRoute('app_motorcycle');
        }

        // Affiche le formulaire (vierge, ou réaffiché avec les erreurs)
        return $this->render('motorcycle/new.html.twig', [
            'form' => $form,
        ]);
    }

    // Consultation d'une moto, accessible à tout membre connecté
    #[Route('/show/{id}', name: 'app_motorcycle_show', methods: ['GET'])]
    public function show(Motorcycle $motorcycle) : Response
    {
        return $this->render('motorcycle/show.html.twig', [
            'motorcycle' => $motorcycle,
        ]);
    }

    // Édition d'une moto : réservée à son propriétaire
    #[Route('/{id}/edit', name: 'app_motorcycle_edit', methods: ['GET', 'POST'])]
    public function edit(Motorcycle $motorcycle, Request $request, EntityManagerInterface $em, FileUploader $fileUploader) : Response
    {
        //  Sécurité : seul le propriétaire peut modifier sa moto (sinon 403)
        if ($motorcycle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette moto ne vous appartient pas.');
        }

        // Formulaire pré-rempli avec la moto existante
        $form = $this->createForm(MotorcycleType::class, $motorcycle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Récupère le fichier envoyé (champ non mappé du form)
            $photoFile = $form->get('photoFile')->getData();

            // S'il y a un nouveau fichier, on le traite ; sinon l'ancienne photo est conservée
            if ($photoFile) {
                $newFilename = $fileUploader->upload($photoFile, 'motorcycles');
                $motorcycle->setPhoto($newFilename);
            }

            // Objet DÉJÀ existant => pas de persist, juste flush (UPDATE)
            $em->flush();

            $this->addFlash('success', 'Moto modifiée.');

            return $this->redirectToRoute('app_motorcycle');
        }
        return $this->render('motorcycle/edit.html.twig', [
            'form' => $form,
            'motorcycle' => $motorcycle,
        ]);
    }

    // Suppression d'une moto : POST uniquement (jamais via un lien), propriétaire only
    #[Route('/delete/{id}', name: 'app_motorcycle_delete', methods: ['POST'])]
    public function delete(Motorcycle $motorcycle, Request $request, EntityManagerInterface $em) : Response
    {
        //  Sécurité : seul le propriétaire peut supprimer sa moto (sinon 403)
        if ($motorcycle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Cette moto ne t'apartient pas!");
        }

        //  On ne supprime que si le token CSRF est valide (protège contre les requêtes forgées)
        if ($this->isCsrfTokenValid('delete' . $motorcycle->getId(), $request->request->get('_token'))) {
            // remove() marque pour suppression, flush() exécute le DELETE
            $em->remove($motorcycle);
            $em->flush();
            $this->addFlash('success', 'Moto supprimée.');
        }

        // Redirige toujours vers le garage (que la suppression ait eu lieu ou non)
        return $this->redirectToRoute('app_motorcycle');
    }
}
