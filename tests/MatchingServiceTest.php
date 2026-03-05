<?php

namespace App\Tests\Service;

use App\Entity\Demande;
use App\Service\MatchingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MatchingServiceTest extends TestCase
{
    /**
     * Règle métier 1 : Si l'offre est absente de la demande, le score doit être 0.
     */
    public function testScoreEstZeroSiOffreManquante(): void
    {
        // Création d'un mock pour les paramètres Symfony
        $params = $this->createMock(ParameterBagInterface::class);
        $service = new MatchingService($params);

        $demande = new Demande(); // Demande sans offre
        $score = $service->calculateScore($demande);

        $this->assertEquals(0, $score, "La logique métier impose un score de 0 sans offre.");
    }
}