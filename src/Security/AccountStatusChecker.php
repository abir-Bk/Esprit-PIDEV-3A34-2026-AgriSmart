<?php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountStatusChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() === 'pending') {
            throw new CustomUserMessageAccountStatusException('Compte en attente.');
        }

        if ($user->getStatus() === 'desactive') {
            throw new CustomUserMessageAccountStatusException('Compte désactivé.');
        }
    }

    public function checkPostAuth(UserInterface $user): void {}
}