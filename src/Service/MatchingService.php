<?php

namespace App\Service;

use App\Entity\Demande;
use Smalot\PdfParser\Parser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MatchingService
{
    /** @var ParameterBagInterface */
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function calculateScore(Demande $demande): int
    {
        $offre = $demande->getOffre();
        
        // CORRECTION LIGNE 24 : PHPStan sait que getUser() existe dans l'entité Demande.
        // On supprime les method_exists() inutiles.
        $user = $demande->getUser();

        if (!$offre || !$user) {
            return 0;
        }

        // Chemin du CV sécurisé pour PHPStan Niveau 8
        $projectDir = $this->params->get('kernel.project_dir');
        $baseDir = is_string($projectDir) ? $projectDir : '';
        $cvPath = $baseDir . '/public/uploads/cv/' . (string)$demande->getCv();

        if (!file_exists($cvPath)) {
            return 0;
        }
        $parser = new Parser();
        try {
            $pdf = $parser->parseFile($cvPath);
            $textRaw = $pdf->getText();
            $text = mb_strtolower($textRaw);
        } catch (\Exception $e) {
            return 0;
        }

        $totalScore = 0;

    // 1. IDENTITÉ (+10 pts)
        $nom = method_exists($user, 'getNom') ? $user->getNom() : null;
        $prenom = method_exists($user, 'getPrenom') ? $user->getPrenom() : null;

        if ($nom && str_contains($text, mb_strtolower((string)$nom))) $totalScore += 5;
        if ($prenom && str_contains($text, mb_strtolower((string)$prenom))) $totalScore += 5;

        // 2. DIPLÔMES (+25 pts)
        $diplomes = ['ingenieur' => 25, 'master' => 20, 'licence' => 15, 'technicien' => 10, 'bac' => 5];
        foreach ($diplomes as $keyword => $points) {
            if (str_contains($text, $keyword)) {
                $totalScore += $points;
                break; 
            }
        }

        // 3. TITRE MÉTIER (+25 pts)
        $jobTitle = mb_strtolower((string)$offre->getTitle());
        if (str_contains($text, $jobTitle)) $totalScore += 25;

        // 4. BONUS STRUCTURE (+15 pts)
        preg_match_all('/[A-Z]{3,}/', (string)$offre->getDescription(), $matches);
        
        // CORRECTION LIGNE 70 : L'index [0] existe toujours après preg_match_all.
        // On enlève le "?? []" qui est inutile.
        foreach (array_unique($matches[0]) as $stuc) {
            if (str_contains(strtoupper($textRaw), (string)$stuc)) {
                $totalScore += 15;
                break;
            }
        }

        // 5. COMPÉTENCES (+25 pts max)
        $description = mb_strtolower(strip_tags((string)$offre->getDescription()));
        $words = array_filter(explode(' ', str_replace(['.', ',', '!'], ' ', $description)), fn($w) => strlen($w) > 4);
        $uniqueKeywords = array_unique($words);
        
        if (count($uniqueKeywords) > 0) {
            $matchCount = 0;
            foreach ($uniqueKeywords as $word) {
                if (str_contains($text, $word)) $matchCount++;
            }
            $totalScore += ($matchCount / count($uniqueKeywords)) * 25;
        }

        return (int) min(round($totalScore), 100);
    }
}