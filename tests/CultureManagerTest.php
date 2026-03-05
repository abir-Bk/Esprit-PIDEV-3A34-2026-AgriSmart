<?php

namespace App\Tests\Service;

use App\Entity\Culture;
use App\Service\CultureManager;
use PHPUnit\Framework\TestCase;

class CultureManagerTest extends TestCase
{
    // ✅ TEST 1 : Culture valide
    public function testValidCulture(): void
    {
        $culture = new Culture();
        $culture->setTypeCulture('Blé');
        $culture->setVariete('Blé tendre');
        $culture->setDatePlantation(new \DateTime('2024-01-01'));
        $culture->setDateRecoltePrevue(new \DateTime('2024-06-01'));
        $culture->setStatut('En croissance');

        $manager = new CultureManager();
        $this->assertTrue($manager->validate($culture));
    }

    // ❌ TEST 2 : Type de culture vide
    public function testCultureWithoutType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type de culture est obligatoire');

        $culture = new Culture();
        $culture->setTypeCulture('');
        $culture->setVariete('Blé tendre');
        $culture->setDatePlantation(new \DateTime('2024-01-01'));
        $culture->setDateRecoltePrevue(new \DateTime('2024-06-01'));
        $culture->setStatut('En croissance');

        $manager = new CultureManager();
        $manager->validate($culture);
    }

    // ❌ TEST 3 : Variété vide
    public function testCultureWithoutVariete(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La variété est obligatoire');

        $culture = new Culture();
        $culture->setTypeCulture('Blé');
        $culture->setVariete('');
        $culture->setDatePlantation(new \DateTime('2024-01-01'));
        $culture->setDateRecoltePrevue(new \DateTime('2024-06-01'));
        $culture->setStatut('En croissance');

        $manager = new CultureManager();
        $manager->validate($culture);
    }

    // ❌ TEST 4 : Date de récolte avant plantation
    public function testCultureWithInvalidDates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de récolte doit être postérieure à la date de plantation');

        $culture = new Culture();
        $culture->setTypeCulture('Blé');
        $culture->setVariete('Blé tendre');
        $culture->setDatePlantation(new \DateTime('2024-06-01'));
        $culture->setDateRecoltePrevue(new \DateTime('2024-01-01'));
        $culture->setStatut('En croissance');

        $manager = new CultureManager();
        $manager->validate($culture);
    }

    // ❌ TEST 5 : Statut invalide
    public function testCultureWithInvalidStatut(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Statut invalide');

        $culture = new Culture();
        $culture->setTypeCulture('Blé');
        $culture->setVariete('Blé tendre');
        $culture->setDatePlantation(new \DateTime('2024-01-01'));
        $culture->setDateRecoltePrevue(new \DateTime('2024-06-01'));
        $culture->setStatut('StatutInvalide');

        $manager = new CultureManager();
        $manager->validate($culture);
    }
}