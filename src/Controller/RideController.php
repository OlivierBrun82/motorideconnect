<?php

namespace App\Controller;

use App\Service\Paginator;
use App\Service\RideDuration;
use App\Form\RideType;
use App\Entity\Ride;
use App\Entity\User;
use App\Enum\RideStatus;
use App\Exception\RideRegistrationException;
use App\Form\CommentType;
use App\Form\RideFilterType;
use App\Repository\MotorcycleRepository;
use App\Repository\RideRepository;
use App\Service\RideCancellationManager;
use App\Service\RideRegistrationManager;
use App\Entity\Comment;
use App\Service\ModerationManager;
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
    public function index(Request $request, RideRepository $rideRepository, RideDuration $rideDuration, Paginator $paginator): Response
    {
        $form = $this->createForm(RideFilterType::class);
        $form->handleRequest($request);

        $filters = $form->getData() ?? [];
        $rides = $rideRepository->findByFilters($filters);

        // Filtre duree applique en PHP : la duree n'est stockee nulle part
        // (entites figees), elle se recalcule a chaque fois -> pas filtrable en SQL.
        // Sans tranche selectionnee on ne filtre rien : les balades sans endTime restent visibles.
        // La pagination doit donc etre en PHP et venir APRES ce filtre,
        // un LIMIT/OFFSET SQL donnerait des pages fausses sans lever d'erreur.
        if (!empty($filters['duration'])) {
            $rides = array_values(array_filter(
                $rides,
                fn (Ride $ride) => $rideDuration->matches($ride, $filters['duration'])
            ));
        }

        // Decoupage en pages APRES le filtre duree : sur un tableau deja filtre,
        // donc le compteur total et la taille des pages sont exacts.
        $page = $request->query->getInt('page', 1);
        $pagination = $paginator->paginate($rides, $page);

        // Nombre de balades par organisateur, indexe par id d'organisateur.
        // Construit APRES la pagination : seules les balades affichees comptent,
        // soit 5 organisateurs au maximum. Le ??= evite de recompter deux fois
        // le meme membre s'il organise plusieurs balades de la page.
        // Le tableau est bati depuis ces memes balades, donc aucune cle ne peut
        // manquer cote Twig, y compris pour un organisateur a zero balade active.
        $organizerRideCounts = [];
        foreach ($pagination->items as $ride) {
            $organizerId = $ride->getUser()->getId();
            $organizerRideCounts[$organizerId] ??= $rideRepository->countByOrganizer($ride->getUser());
        }

        return $this->render('ride/index.html.twig', [
            'form' => $form,
            'pagination' => $pagination,
            'organizerRideCounts' => $organizerRideCounts,
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
        // statut 422 si soumission invalide, pour que Turbo remplace le form avec ses erreurs
        return $this->render('ride/new.html.twig', [
            'form' => $form,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    #[Route('/show/{id}', name: 'app_ride_show', methods:['GET', 'POST'])]
        public function show(Ride $ride, Request $request, EntityManagerInterface $em, RideDuration $rideDuration, RideRepository $rideRepository) : Response
        {
            // construction du formulaire de commentaire (Comment vierge, rempli par le form)
            $comment = new Comment();
            $form = $this->createForm(CommentType::class, $comment);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user = $this->getUser();

                // la balade est active si elle n'est ni annulee ni passee
                $isActive = $ride->getStatut() !== RideStatus::Canceled && $ride->getMeetingDatetime() > new \DateTimeImmutable();

                // on ne publie que si l'utilisateur est participant, la balade active
                // et le message non vide (blocage silencieux, sans message d'erreur)
                if ($ride->getParticipants()->contains($user) && $isActive && trim((string) $comment->getMessage()) !== '') {
                    // cablages serveur : auteur, balade et date (jamais dans le form)
                    $comment->setUser($user);
                    $comment->setRide($ride);
                    $comment->setCreatedAt(new \DateTimeImmutable());

                    $em->persist($comment);
                    $em->flush();

                    // on redirige vers la fiche pour eviter le re-post au refresh
                    return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
                }
            }

            // affichage de la fiche + formulaire de commentaire
            return $this->render('ride/show.html.twig', [
                'ride' => $ride,
                'form' => $form,
                'duration' => $rideDuration->format($ride),
                // indicateur de fiabilite de l'organisateur (annulations exclues)
                'organizerRideCount' => $rideRepository->countByOrganizer($ride->getUser()),
            ]);

        }
    
    #[Route('/edit/{id}', name: 'app_ride_edit', methods:['GET', 'POST'])]
        public function edit(Ride $ride, Request $request, EntityManagerInterface $em) : Response
        {
            // organisateur OU admin (override) peuvent editer
            if ($ride->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
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

            // statut 422 si soumission invalide, pour que Turbo remplace le form avec ses erreurs
            return $this->render('ride/edit.html.twig', [
                'form' => $form,
                'ride' => $ride
            ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
        }
    
    #[Route('/delete/{id}', name: 'app_ride_delete', methods:['POST'])]
    public function delete(Ride $ride, Request $request, EntityManagerInterface $em) : Response
    {
        // vérification que c'est l'utilisateur de cette balade qui demande le delete
        // organisateur OU admin (override) peuvent supprimer
        if ($ride->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
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

            // desinscription reussie : on renvoie vers le listing,
            // rester sur la fiche d'une balade qu'on vient de quitter n'a pas de sens
            return $this->redirectToRoute('app_ride');
        } catch (RideRegistrationException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        // en cas d'echec on reste sur la fiche : le message parle de cette balade
        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }

    #[Route('/cancel/{id}', name:'app_ride_cancel', methods:['POST'])]
    public function cancel(Ride $ride, Request $request, RideCancellationManager $cancellationManager) : Response
    {
        // organisateur OU admin (override) peuvent annuler
        if ($ride->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException("Tu n'es pas le créateur de cette balade.");
        }

        if ($this->isCsrfTokenValid('cancel' . $ride->getId(), $request->request->get('_token'))) {
            // le bouton est masque dans le template, mais un POST direct passerait :
            // sans ce garde-fou une balade deja annulee ou passee pourrait etre re-annulee
            if ($ride->getStatut() === RideStatus::Canceled || $ride->getMeetingDatetime() <= new \DateTimeImmutable()) {
                $this->addFlash('danger', "Cette balade ne peut plus être annulée.");

                return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
            }

            $reason = trim((string) $request->request->get('reason'));
            $reasonLength = mb_strlen($reason);

            // la raison n'est jamais stockee (entites figees) : elle sert uniquement au mail
            if ($reasonLength < 10 || $reasonLength > 500) {
                $this->addFlash('danger', "Merci d'indiquer la raison de l'annulation (10 à 500 caractères).");

                return $this->redirectToRoute('app_ride_edit', ['id' => $ride->getId()]);
            }

            // le service passe le statut a Canceled, flush, puis notifie les participants
            $cancellationManager->cancel($ride, $reason);
            $this->addFlash('success', "Balade annulée. Les participants ont été prévenus par email.");
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

    #[Route('/comment/delete/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function deleteComment(Comment $comment, Request $request, EntityManagerInterface $em, ModerationManager $moderation) : Response
    {
        // on récupère la balade à laquelle appartient le commentaire et son utilisateur
        $ride = $comment->getRide();
        $user = $this->getUser();

        // seul l'auteur ou l'organisateur peuvent supprimer le commentaire
        // auteur, organisateur OU admin (override) peuvent supprimer le commentaire
        if ($comment->getUser() !== $user && $ride->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException("Tu ne peux pas supprimer ce commentaire.");
        }

        $author = $comment->getUser();

        // supprimer son propre message est un retrait volontaire, pas une sanction :
        // sans cette distinction, trois repentirs suffiraient a se faire bannir soi-meme
        $isModeration = $author !== $user;

        // un organisateur ne peut pas sanctionner un administrateur (meme regle que strikeParticipant)
        if ($isModeration && !$this->isGranted('ROLE_ADMIN') && in_array('ROLE_ADMIN', $author->getRoles(), true)) {
            throw $this->createAccessDeniedException("Tu ne peux pas sanctionner un administrateur.");
        }

        // vérification du Csrf et supression du commentaire
        if ($this->isCsrfTokenValid('delete_comment' . $comment->getId(), $request->request->get('_token'))) {
            $reason = trim((string) $request->request->get('reason'));

            // en moderation le motif est obligatoire : il part en strike a l'auteur (CDC).
            // on refuse la suppression plutot que de l'ignorer, sinon message supprime sans strike
            if ($isModeration && mb_strlen($reason) < 10) {
                $this->addFlash('danger', "Indique le motif de la suppression (10 caractères minimum), il sera envoyé à l'auteur.");

                return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
            }

            $em->remove($comment);
            $em->flush();

            // strike apres la suppression : si le mailer casse, le message litigieux
            // a quand meme disparu. addStrike gere l'email et le ban auto a 3
            if ($isModeration) {
                $moderation->addStrike($author, $reason);
                $this->addFlash('success', "Message supprimé, un avertissement a été envoyé à son auteur.");
            } else {
                $this->addFlash('success', "Ton commentaire a été supprimé.");
            }
        }

        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }

    #[Route('/{ride}/participant/{user}/strike', name: 'app_ride_strike', methods:['POST'])]
    public function strikeParticipant(Ride $ride, User $user, Request $request, ModerationManager $moderation) : Response
    {
        // Seul l'organisateur de cette balade peut moderer
        if ($ride->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Tu n'es pas l'organisateur de cette balade.");
        }

        // cible uniquement un participant de cette balade, et pas l'organisateur lui-meme
        if ($user === $ride->getUser() || !$ride->getParticipants()->contains($user)) {
            throw $this->createAccessDeniedException("Cette personne ne participe pas à ta balade.");
        }

        // un organisateur ne peut pas sanctionner un administrateur
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw $this->createAccessDeniedException("Tu ne peux pas sanctionner un administrateur.");
        }

        // verification CSRF puis attribution du strike (le service gere email + ban auto)
        if ($this->isCsrfTokenValid('strike_participant' . $user->getId(), $request->request->get('_token'))) {
            // motif obligatoire : pas de strike si le motif est vide
            $reason = trim((string) $request->request->get('reason'));
            if ($reason !== '') {
                $moderation->addStrike($user, $reason);
            }
        }

        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }

    #[Route('/{ride}/participant/{user}/exclude', name: 'app_ride_exclude', methods:['POST'])]
    public function excludeParticipant(Ride $ride, User $user, Request $request, EntityManagerInterface $em) : Response
    {
        // Seul l'organisateur de cette balade peut exclure
        if ($ride->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Tu n'es pas l'organisateur de cette balade.");
        }

        // cible uniquement un participant de cette balade, et pas l'organisateur lui-meme
        if ($user === $ride->getUser() || !$ride->getParticipants()->contains($user)) {
            throw $this->createAccessDeniedException("Cette personne ne participe pas à ta balade.");
        }

        // un organisateur ne peut pas exclure un administrateur
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw $this->createAccessDeniedException("Tu ne peux pas exclure un administrateur.");
        }

        // verification CSRF puis exclusion (action locale : retrait de participants, pas un ban)
        if ($this->isCsrfTokenValid('exclude_participant' . $user->getId(), $request->request->get('_token'))) {
            $ride->removeParticipant($user);

            // une place se libere : si la balade etait complete, elle redevient ouverte
            if ($ride->getStatut() === RideStatus::Full) {
                $ride->setStatut(RideStatus::Open);
            }

            $em->flush();
        }

        return $this->redirectToRoute('app_ride_show', ['id' => $ride->getId()]);
    }
}
