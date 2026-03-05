<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<User> */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

   // ✅ APRÈS
public function save(User $user, bool $flush = false): void
{
    $this->getEntityManager()->persist($user);
    if ($flush) {
        $this->getEntityManager()->flush();
    }
}
    public function findByEmail(string $email): ?User
    {
        $user = $this->findOneBy(['email' => $email]);

        return $user instanceof User ? $user : null;
    }
}