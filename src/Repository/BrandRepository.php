<?php

namespace App\Repository;

use App\Entity\Brand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Brand>
 */
class BrandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Brand::class);
    }

    // Recherche une marque par son nom, insensible a la casse et aux espaces.
    public function findOneByNameInsensitive(string $name) : ?Brand
    {
        return $this->createQueryBuilder('b')
            ->andWhere('LOWER(b.name) = :name')
            ->setParameter('name', mb_strtolower(trim($name)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
