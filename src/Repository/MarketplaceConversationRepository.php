<?php

namespace App\Repository;

use App\Entity\MarketplaceConversation;
use App\Entity\Produit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarketplaceConversation>
 */
class MarketplaceConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketplaceConversation::class);
    }

    /**
     * @return MarketplaceConversation[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.buyer = :u OR c.seller = :u')
            ->setParameter('u', $user)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserConversationById(User $user, int $conversationId): ?MarketplaceConversation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.buyer = :u OR c.seller = :u')
            ->setParameter('id', $conversationId)
            ->setParameter('u', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByParticipantsAndProduit(Produit $produit, User $buyer, User $seller): ?MarketplaceConversation
    {
        return $this->findOneBy([
            'produit' => $produit,
            'buyer' => $buyer,
            'seller' => $seller,
        ]);
    }
}
