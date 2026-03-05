<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    // utilisateur valide
    public function testUtilisateurValide(): void
    {
        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Ali');
        $user->setEmail('ahmed@gmail.com');
        $user->setRole('agriculteur');

        $this->assertTrue($this->manager->validate($user));
    }

    //  prénom vide
    public function testPrenomVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom est obligatoire');

        $user = new User();
        $user->setFirstName('');
        $user->setLastName('Ben Ali');
        $user->setEmail('ahmed@gmail.com');
        $user->setRole('agriculteur');

        $this->manager->validate($user);
    }

    //  nom vide
    public function testNomVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('');
        $user->setEmail('ahmed@gmail.com');
        $user->setRole('agriculteur');

        $this->manager->validate($user);
    }

    // email invalide
    public function testEmailInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email invalide');

        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Ali');
        $user->setEmail('email_invalide');
        $user->setRole('agriculteur');

        $this->manager->validate($user);
    }

    // email vide
    public function testEmailVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email est obligatoire');

        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Ali');
        $user->setEmail('');
        $user->setRole('agriculteur');

        $this->manager->validate($user);
    }

    //  rôle invalide
public function testRoleInvalide(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid role: superadmin'); 

    $user = new User();
    $user->setFirstName('Ahmed');
    $user->setLastName('Ben Ali');
    $user->setEmail('ahmed@gmail.com');
    $user->setRole('superadmin');

    $this->manager->validate($user);
}
    // tous les rôles valides
    public function testTousLesRolesValides(): void
    {
        $roles = ['admin', 'employee', 'agriculteur', 'fournisseur'];

        foreach ($roles as $role) {
            $user = new User();
            $user->setFirstName('Test');
            $user->setLastName('User');
            $user->setEmail('test@gmail.com');
            $user->setRole($role);

            $this->assertTrue($this->manager->validate($user));
        }
    }
}