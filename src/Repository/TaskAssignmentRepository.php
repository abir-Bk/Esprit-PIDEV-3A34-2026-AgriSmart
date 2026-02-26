<?php

namespace App\Repository;

use App\Entity\TaskAssignment;
use App\Entity\User;
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
    public function findForWorker(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.worker = :user')
            ->setParameter('user', $user)
            ->leftJoin('a.task', 't')
            ->addSelect('t')
            ->orderBy('t.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
