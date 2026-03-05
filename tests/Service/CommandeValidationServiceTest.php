<?php

namespace App\Tests\Service;

use App\Entity\Commande;
use App\Entity\User;
use App\Service\CommandeValidationService;
use PHPUnit\Framework\TestCase;

class CommandeValidationServiceTest extends TestCase
{
    public function testValidateCommandeOk(): void
    {
        $service = new CommandeValidationService();

        $commande = new Commande();
        $commande->setClient($this->createUser());
        $commande->setAdresseLivraison('12 Rue des Oliviers, Tunis');
        $commande->setMontantTotal(150.0);

        $this->assertTrue($service->validateCommande($commande));
    }

    public function testValidateCommandeRule1Fails(): void
    {
        $service = new CommandeValidationService();

        $commande = new Commande();
        $commande->setClient($this->createUser());
        $commande->setAdresseLivraison('   ');
        $commande->setMontantTotal(150.0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Adresse de livraison obligatoire.');

        $service->validateCommande($commande);
    }

    public function testValidateCommandeRule2Fails(): void
    {
        $service = new CommandeValidationService();

        $commande = new Commande();
        $commande->setClient($this->createUser());
        $commande->setAdresseLivraison('12 Rue des Oliviers, Tunis');
        $commande->setMontantTotal(0.0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le montant total doit être supérieur à 0.');

        $service->validateCommande($commande);
    }

    private function createUser(): User
    {
        return (new User())
            ->setFirstName('Dev')
            ->setLastName('Tester')
            ->setEmail('dev.tester@example.com')
            ->setPassword('secret');
    }
}
