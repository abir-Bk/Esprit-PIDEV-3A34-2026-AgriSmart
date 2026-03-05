<?php

namespace App\Tests\Service;

use App\Entity\Parcelle;
use App\Service\ParcelleManager;
use PHPUnit\Framework\TestCase;

class ParcelleManagerTest extends TestCase
{
    // ✅ TEST 1 : Parcelle valide
    public function testValidParcelle(): void
    {
        $parcelle = new Parcelle();
        $parcelle->setNom('Parcelle Nord');
        $parcelle->setSurface(5.5);
        $parcelle->setLatitude(36.8);
        $parcelle->setLongitude(10.2);
        $parcelle->setTypeSol('Argileux');

        $manager = new ParcelleManager();
        $this->assertTrue($manager->validate($parcelle));
    }

    // ❌ TEST 2 : Nom vide
    public function testParcelleWithoutNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la parcelle est obligatoire');

        $parcelle = new Parcelle();
        $parcelle->setNom('');
        $parcelle->setSurface(5.5);
        $parcelle->setLatitude(36.8);
        $parcelle->setLongitude(10.2);
        $parcelle->setTypeSol('Argileux');

        $manager = new ParcelleManager();
        $manager->validate($parcelle);
    }

    // ❌ TEST 3 : Surface négative
    public function testParcelleWithNegativeSurface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La surface doit être un nombre positif');

        $parcelle = new Parcelle();
        $parcelle->setNom('Parcelle Nord');
        $parcelle->setSurface(-5.0);
        $parcelle->setLatitude(36.8);
        $parcelle->setLongitude(10.2);
        $parcelle->setTypeSol('Argileux');

        $manager = new ParcelleManager();
        $manager->validate($parcelle);
    }

    // ❌ TEST 4 : Surface trop grande
    public function testParcelleWithSurfaceTooLarge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La surface ne peut pas dépasser 10 000 hectares');

        $parcelle = new Parcelle();
        $parcelle->setNom('Parcelle Nord');
        $parcelle->setSurface(99999.0);
        $parcelle->setLatitude(36.8);
        $parcelle->setLongitude(10.2);
        $parcelle->setTypeSol('Argileux');

        $manager = new ParcelleManager();
        $manager->validate($parcelle);
    }

    // ❌ TEST 5 : Latitude invalide
    public function testParcelleWithInvalidLatitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La latitude doit être comprise entre -90 et 90');

        $parcelle = new Parcelle();
        $parcelle->setNom('Parcelle Nord');
        $parcelle->setSurface(5.5);
        $parcelle->setLatitude(200.0);
        $parcelle->setLongitude(10.2);
        $parcelle->setTypeSol('Argileux');

        $manager = new ParcelleManager();
        $manager->validate($parcelle);
    }

    // ❌ TEST 6 : Type de sol invalide
    public function testParcelleWithInvalidTypeSol(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type de sol invalide');

        $parcelle = new Parcelle();
        $parcelle->setNom('Parcelle Nord');
        $parcelle->setSurface(5.5);
        $parcelle->setLatitude(36.8);
        $parcelle->setLongitude(10.2);
        $parcelle->setTypeSol('TypeInvalide');

        $manager = new ParcelleManager();
        $manager->validate($parcelle);
    }
}