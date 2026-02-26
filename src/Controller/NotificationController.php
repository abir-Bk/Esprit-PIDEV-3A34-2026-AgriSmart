<?php

namespace App\Controller;

use App\Entity\AdminNotification;
use App\Repository\AdminNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/notifications')]
#[IsGranted('ROLE_ADMIN')]
class NotificationController extends AbstractController
{
    public function __construct(
        private AdminNotificationRepository $repo,
        private EntityManagerInterface      $em,
    ) {}

    // ── Polling endpoint — returns unread count + latest notifications ──
    #[Route('/poll', name: 'admin_notifications_poll', methods: ['GET'])]
    public function poll(): JsonResponse
    {
        $unread        = $this->repo->countUnread();
        $notifications = $this->repo->findRecent(10);

        $data = array_map(fn(AdminNotification $n) => [
            'id'        => $n->getId(),
            'title'     => $n->getTitle(),
            'message'   => $n->getMessage(),
            'type'      => $n->getType(),
            'link'      => $n->getLink(),
            'isRead'    => $n->isRead(),
            'createdAt' => $n->getCreatedAt()->format('Y-m-d H:i'),
            'user'      => $n->getRelatedUser()?->getFirstName() . ' ' . $n->getRelatedUser()?->getLastName(),
        ], $notifications);

        return $this->json([
            'unread'        => $unread,
            'notifications' => $data,
        ]);
    }

    // ── Mark one as read ────────────────────────────────────────────────
    #[Route('/{id}/read', name: 'admin_notifications_read', methods: ['POST'])]
    public function markRead(AdminNotification $notification): JsonResponse
    {
        $notification->setIsRead(true);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    // ── Mark all as read ────────────────────────────────────────────────
    #[Route('/read-all', name: 'admin_notifications_read_all', methods: ['POST'])]
    public function markAllRead(): JsonResponse
    {
        $this->em->createQuery(
            'UPDATE App\Entity\AdminNotification n SET n.isRead = true WHERE n.isRead = false'
        )->execute();

        return $this->json(['success' => true]);
    }
}