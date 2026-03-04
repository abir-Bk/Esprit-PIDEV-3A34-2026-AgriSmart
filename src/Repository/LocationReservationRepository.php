<?php

namespace App\Repository;

use App\Entity\LocationReservation;
use App\Entity\Produit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LocationReservation>
 */
class LocationReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocationReservation::class);
    }

    /**
     * @return array<int,array{start:string,end:string}>
     */
    public function findReservedRangesForProduit(Produit $produit): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.startDate AS startDate, r.endDate AS endDate')
            ->andWhere('r.produit = :produit')
            ->andWhere('r.status = :status')
            ->setParameter('produit', $produit)
            ->setParameter('status', LocationReservation::STATUS_ACTIVE)
            ->orderBy('r.startDate', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $row): array {
            $start = $row['startDate'] instanceof \DateTimeInterface
                ? $row['startDate']->format('Y-m-d')
                : (new \DateTimeImmutable((string) $row['startDate']))->format('Y-m-d');
            $end = $row['endDate'] instanceof \DateTimeInterface
                ? $row['endDate']->format('Y-m-d')
                : (new \DateTimeImmutable((string) $row['endDate']))->format('Y-m-d');

            return ['start' => $start, 'end' => $end];
        }, $rows);
    }

    public function hasOverlap(Produit $produit, \DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.produit = :produit')
            ->andWhere('r.status = :status')
            ->andWhere('r.startDate <= :endDate')
            ->andWhere('r.endDate >= :startDate')
            ->setParameter('produit', $produit)
            ->setParameter('status', LocationReservation::STATUS_ACTIVE)
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @return LocationReservation[]
     */
    public function findForVendeur(User $vendeur): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.produit', 'p')
            ->addSelect('p')
            ->innerJoin('r.locataire', 'u')
            ->addSelect('u')
            ->andWhere('p.vendeur = :vendeur')
            ->setParameter('vendeur', $vendeur)
            ->orderBy('r.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
