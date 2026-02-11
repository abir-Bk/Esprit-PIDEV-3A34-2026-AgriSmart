<?php

namespace App\Repository;

use App\Entity\Parcelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Parcelle>
 */
class ParcelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parcelle::class);
    }
    public function findBySearchQuery($user, $searchTerm, $sortBy, $direction)
{
    $qb = $this->createQueryBuilder('p')
        ->where('p.user = :user')
        ->setParameter('user', $user);

    if (!empty($searchTerm)) {
        $qb->andWhere('p.nom LIKE :search') // Remplacez 'nom' par le champ souhaité
           ->setParameter('search', '%'.$searchTerm.'%');
    }

    // Sécurisation du tri pour éviter les injections SQL
    $validFields = ['id', 'nom', 'surface']; // Ajoutez vos champs ici
    if (in_array($sortBy, $validFields)) {
        $qb->orderBy('p.' . $sortBy, $direction === 'DESC' ? 'DESC' : 'ASC');
    }

    return $qb->getQuery()->getResult();
}

    //    /**
    //     * @return Parcelle[] Returns an array of Parcelle objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Parcelle
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
