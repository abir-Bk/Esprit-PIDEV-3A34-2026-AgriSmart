<?php

namespace App\Tests\Service;

use App\Entity\Produit;
use App\Service\ProduitValidationService;
use PHPUnit\Framework\TestCase;

class ProduitValidationServiceTest extends TestCase
{
    public function testValidateProduitOk(): void
    {
        $service = new ProduitValidationService();

        $produit = new Produit();
        $produit->setNom('Pommes de terre');
        $produit->setPrix(35.0);

        $this->assertTrue($service->validateProduit($produit));
    }

    public function testValidateProduitRule1Fails(): void
    {
        $service = new ProduitValidationService();

        $produit = new Produit();
        $produit->setNom('   ');
        $produit->setPrix(35.0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du produit est obligatoire.');

        $service->validateProduit($produit);
    }

    public function testValidateProduitRule2Fails(): void
    {
        $service = new ProduitValidationService();

        $produit = new Produit();
        $produit->setNom('Pommes de terre');
        $produit->setPrix(0.0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix du produit doit être supérieur à 0.');

        $service->validateProduit($produit);
    }
}
