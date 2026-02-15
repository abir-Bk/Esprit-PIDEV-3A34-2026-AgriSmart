<?php

namespace App\Service;

use App\Entity\Demande;
use Smalot\PdfParser\Parser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MatchingService
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function calculateScore(Demande $demande): int
    {
        $offre = $demande->getOffre();
        if (!$offre) return 0;

        // Chemin vers ton dossier public/uploads/cv/
        $cvPath = $this->params->get('kernel.project_dir') . '/public/uploads/cv/' . $demande->getCv();
        
        if (!file_exists($cvPath)) return 0;

        $parser = new Parser();
        try {
            $pdf = $parser->parseFile($cvPath);
            // On met tout en minuscules pour que "Mécanique" match avec "mécanique"
            $text = mb_strtolower($pdf->getText());
        } catch (\Exception $e) {
            return 0;
        }

        $score = 0;
        $title = mb_strtolower($offre->getTitle());
        $description = mb_strtolower(strip_tags($offre->getDescription()));

        // 1. Matching sur le titre du poste (+50 pts)
        if (str_contains($text, $title)) {
            $score += 50;
        }

        // 2. Matching sur les mots-clés de la description (+10 pts par mot trouvé)
        // On sépare la description en mots de plus de 4 lettres pour éviter les "le, la, de..."
        $keywords = array_filter(explode(' ', str_replace(['.', ',', '!'], ' ', $description)), fn($w) => strlen($w) > 4);
        
        foreach (array_unique($keywords) as $word) {
            if (str_contains($text, $word)) {
                $score += 10;
            }
        }

        return min($score, 100); // Plafond à 100%
    }
}