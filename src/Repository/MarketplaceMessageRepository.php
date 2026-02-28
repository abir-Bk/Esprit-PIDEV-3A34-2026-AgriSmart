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

    /**
     * @return array{messageIds:int[],readAt:\DateTimeImmutable|null}
     */
    public function markConversationAsReadForUser(MarketplaceConversation $conversation, User $user): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m.id AS id')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = false')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        $messageIds = array_map(static fn(array $row): int => (int) $row['id'], $rows);
        if ($messageIds === []) {
            return [
                'messageIds' => [],
                'readAt' => null,
            ];
        }

        $readAt = new \DateTimeImmutable();

        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':read')
            ->set('m.readAt', ':readAt')
            ->andWhere('m.id IN (:messageIds)')
            ->setParameter('read', true)
            ->setParameter('readAt', $readAt)
            ->setParameter('messageIds', $messageIds)
            ->getQuery()
            ->execute();

        return [
            'messageIds' => $messageIds,
            'readAt' => $readAt,
        ];
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

    /**
     * @return MarketplaceMessage[]
     */
    public function findReadUpdatesForSender(MarketplaceConversation $conversation, User $sender, int $afterId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.sender = :sender')
            ->andWhere('m.isRead = true')
            ->andWhere('m.readAt IS NOT NULL')
            ->andWhere('m.id > :afterId')
            ->setParameter('conversation', $conversation)
            ->setParameter('sender', $sender)
            ->setParameter('afterId', $afterId)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
