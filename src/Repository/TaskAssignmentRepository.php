<?php

namespace App\Repository;

use App\Entity\TaskAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskAssignment>
 */
class TaskAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskAssignment::class);
    }

    /**
     * Retourne les affectations pour un ouvrier (employé) donné.
     *
     * @return TaskAssignment[]
     */
    public function findForWorker(int $workerId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.workerId = :wid')
            ->setParameter('wid', $workerId)
            ->leftJoin('a.task', 't')
            ->addSelect('t')
            ->orderBy('t.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

