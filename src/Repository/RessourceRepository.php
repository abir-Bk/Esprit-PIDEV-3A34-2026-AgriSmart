<?php

namespace App\Repository;

use App\Entity\Ressource;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ressource>
 */
class RessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    public function findAllWithConsumptionQueryBuilder(?string $search = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.consommations', 'c')
            ->leftJoin('r.user', 'u')
            ->addSelect('c', 'u')
            ->orderBy('r.nom', 'ASC');

        if ($search) {
            $qb->andWhere('r.nom LIKE :search OR u.lastName LIKE :search OR u.firstName LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb;
    }
    /** @return array<int, array{name: string, total: numeric-string}> */
    public function sumStocksByName(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.nom as name, SUM(r.stockRestant) as total')
            ->groupBy('r.nom')
            ->getQuery()
            ->getResult();
    }
}
