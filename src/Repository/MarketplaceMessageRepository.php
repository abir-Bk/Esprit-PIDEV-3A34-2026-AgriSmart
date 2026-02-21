<?php

namespace App\Repository;

use App\Entity\MarketplaceConversation;
use App\Entity\MarketplaceMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarketplaceMessage>
 */
class MarketplaceMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarketplaceMessage::class);
    }

    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->join('m.conversation', 'c')
            ->andWhere('(c.buyer = :u OR c.seller = :u)')
            ->andWhere('m.sender != :u')
            ->andWhere('m.isRead = false')
            ->setParameter('u', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, int>
     */
    public function getUnreadCountByConversationForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.conversation) AS conversationId, COUNT(m.id) AS unreadCount')
            ->join('m.conversation', 'c')
            ->andWhere('(c.buyer = :u OR c.seller = :u)')
            ->andWhere('m.sender != :u')
            ->andWhere('m.isRead = false')
            ->setParameter('u', $user)
            ->groupBy('m.conversation')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['conversationId']] = (int) $row['unreadCount'];
        }

        return $result;
    }

    public function markConversationAsReadForUser(MarketplaceConversation $conversation, User $user): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':read')
            ->set('m.readAt', ':readAt')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = false')
            ->setParameter('read', true)
            ->setParameter('readAt', new \DateTimeImmutable())
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * @return MarketplaceMessage[]
     */
    public function findAfterIdForConversation(MarketplaceConversation $conversation, int $afterId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.id > :afterId')
            ->setParameter('conversation', $conversation)
            ->setParameter('afterId', $afterId)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
