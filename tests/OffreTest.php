<?php
namespace App\Tests\Entity;

use App\Entity\Offre;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OffreTest extends KernelTestCase
{
    public function testOffreInvalideSiTitreVide(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $offre = new Offre();
        $offre->setTitle(""); // Titre vide
        $offre->setDescription("Description test");

        // On récupère le validateur de Symfony
        $errors = $container->get('validator')->validate($offre);
        
        // On attend au moins une erreur sur le champ titre
        $this->assertGreaterThan(0, count($errors));
    }
}