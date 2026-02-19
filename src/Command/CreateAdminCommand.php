<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un compte administrateur',
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        // Pas d’arguments ni d’options nécessaires
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si l'admin existe déjà
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@gmail.com']);
        if ($existingAdmin) {
            $io->warning('Un admin avec cet email existe déjà !');
            return Command::SUCCESS;
        }

        // Création de l’admin
        $user = new User();
        $user->setEmail('admin@gmail.com');
        $user->setRole('admin'); // ← ici on utilise setRole(), pas setRoles()
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'admin123') // mot de passe : admin123
        );
        $user->setFirstName('Admin');
        $user->setLastName('User');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Admin créé avec succès ! Email : admin@gmail.com | Mot de passe : admin123');

        return Command::SUCCESS;
    }
}
