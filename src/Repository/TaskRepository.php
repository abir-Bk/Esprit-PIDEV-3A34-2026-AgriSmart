<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Retourne les tâches dont la date de début est aujourd'hui.
     *
     * @return Task[]
     */
    public function findTodayTasks(\DateTimeInterface $today): array
    {
        $start = (new \DateTimeImmutable($today->format('Y-m-d')))->setTime(0, 0, 0);
        $end = (new \DateTimeImmutable($today->format('Y-m-d')))->setTime(23, 59, 59);

        return $this->createQueryBuilder('t')
            ->andWhere('t.dateDebut BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('t.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

