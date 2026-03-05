<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        if (empty($user->getFirstName())) {
            throw new \InvalidArgumentException('Le prénom est obligatoire');
        }

        if (empty($user->getLastName())) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        if (empty($user->getEmail())) {
            throw new \InvalidArgumentException('L\'email est obligatoire');
        }
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        $allowedRoles = ['admin', 'employee', 'agriculteur', 'fournisseur'];
        if (!in_array($user->getRole(), $allowedRoles, true)) {
            throw new \InvalidArgumentException('Rôle invalide');
        }

        return true;
    }
}