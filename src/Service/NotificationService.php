<?php

namespace App\Service;

use App\Entity\AdminNotification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function create(
        string  $title,
        string  $message,
        string  $type = 'info',
        ?string $link = null,
        ?User   $relatedUser = null,
    ): AdminNotification {
        $notification = new AdminNotification();
        $notification
            ->setTitle($title)
            ->setMessage($message)
            ->setType($type)
            ->setLink($link)
            ->setRelatedUser($relatedUser);

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    public function notifyNewUser(User $user): void
    {
        $this->create(
            title:       'Nouvelle inscription',
            message:     sprintf(
                '%s %s vient de s\'inscrire et attend une validation.',
                $user->getFirstName(),
                $user->getLastName()
            ),
            type:        'warning',
            link:        '/admin/users/pending',
            relatedUser: $user,
        );
    }
}