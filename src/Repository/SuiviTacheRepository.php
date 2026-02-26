<?php

namespace App\Repository;

use App\Entity\SuiviTache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuiviTache>
 */
class SuiviTacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuiviTache::class);
    }

    /**
     * @return SuiviTache[] Returns an array of SuiviTache objects for a specific task
     */
    public function findByTask(int $taskId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.task = :taskId')
            ->setParameter('taskId', $taskId)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
