<?php

namespace App\EventSubscriber;

use App\Event\UserRegisteredEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegistrationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisteredEvent::NAME => 'onUserRegistered',
        ];
    }

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();

        if (in_array($user->getRole(), ['agriculteur', 'fournisseur'], true)) {
            $this->notificationService->notifyNewUser($user);
        }
    }
}