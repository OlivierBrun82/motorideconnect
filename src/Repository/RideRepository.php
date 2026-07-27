<?php

namespace App\Repository;

use App\Entity\Ride;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ride>
 */
class RideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ride::class);
    }

    // Recherche des balades selon des filtres facultatifs (tableau issu du form de filtres)
    public function findByFilters(array $filters) : array
    {
        // Requete de base : uniquement les balades a venir, triees par date de RDV croissante
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.meetingDatetime >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('r.meetingDatetime', 'ASC');

        // Filtres en egalite stricte : cle du filtre => propriete de l'entite Ride
        $equalityFilters = [
            'departmentCode' => 'r.departmentCode',
            'rideType' => 'r.rideType',
            'statut' => 'r.statut',
        ];

        // Pour chaque filtre renseigne, on greffe une condition "champ = valeur"
        // ($field et $key sont maitrises cote code ; seule la valeur passe en parametre lie)
        foreach ($equalityFilters as $key => $field) {
            if (!empty($filters[$key])) {
                $qb->andWhere("$field = :$key")
                ->setParameter($key, $filters[$key]);
            }
        }

        // Filtre date traite a part : operateur >= (et non =), se cumule avec la base
        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('r.meetingDatetime >= :dateFrom')
            ->setParameter('dateFrom', $filters['dateFrom']);
        }

        // Execute la requete DQL et renvoie le tableau d'objets Ride
        return $qb->getQuery()->getResult();
    }
}
