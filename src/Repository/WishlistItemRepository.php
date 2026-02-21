<?php

namespace App\Repository;

use App\Entity\Produit;
use App\Entity\User;
use App\Entity\WishlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistItem>
 */
class WishlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistItem::class);
    }

    /**
     * @param int[] $productIds
     * @return int[]
     */
    public function getWishlistedProductIds(User $user, array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('w')
            ->select('IDENTITY(w.produit) AS productId')
            ->andWhere('w.user = :user')
            ->andWhere('w.produit IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $productIds)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(static fn(array $row): int => (int) $row['productId'], $rows));
    }

    public function findOneByUserAndProduit(User $user, Produit $produit): ?WishlistItem
    {
        return $this->findOneBy([
            'user' => $user,
            'produit' => $produit,
        ]);
    }
}
