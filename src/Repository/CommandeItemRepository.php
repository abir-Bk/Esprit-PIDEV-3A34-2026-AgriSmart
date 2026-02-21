<?php

namespace App\Repository;

use App\Entity\CommandeItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeItem::class);
    }

    /**
     * @return array<int, array{productId: int, qty: int}>
     */
    public function findPurchasedProductsByUser(User $user): array
    {
        $rows = $this->createQueryBuilder('ci')
            ->select('IDENTITY(ci.produit) AS productId, SUM(ci.quantite) AS qty')
            ->innerJoin('ci.commande', 'c')
            ->andWhere('c.client = :user')
            ->setParameter('user', $user)
            ->groupBy('ci.produit')
            ->orderBy('qty', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static function (array $row): array {
            return [
                'productId' => (int) ($row['productId'] ?? 0),
                'qty' => (int) ($row['qty'] ?? 0),
            ];
        }, $rows);
    }
}
