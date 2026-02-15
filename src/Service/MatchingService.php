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
        
        // Récupération sécurisée de l'utilisateur (Candidat)
        $user = method_exists($demande, 'getUsers') ? $demande->getUsers() : 
               (method_exists($demande, 'getUser') ? $demande->getUser() : null);

        if (!$offre || !$user) return 0;

        // Chemin du CV
        $cvPath = $this->params->get('kernel.project_dir') . '/public/uploads/cv/' . $demande->getCv();
        if (!file_exists($cvPath)) return 0;

        $parser = new Parser();
        try {
            $pdf = $parser->parseFile($cvPath);
            $textRaw = $pdf->getText();
            $text = mb_strtolower($textRaw);
        } catch (\Exception $e) {
            return 0;
        }

        $totalScore = 0;

        // 1. IDENTITÉ (+10 pts) - On teste plusieurs noms de méthodes possibles
        $nom = "";
        if (method_exists($user, 'getNom')) $nom = $user->getNom();
        elseif (method_exists($user, 'getLastName')) $nom = $user->getLastName();
        
        $prenom = "";
        if (method_exists($user, 'getPrenom')) $prenom = $user->getPrenom();
        elseif (method_exists($user, 'getFirstName')) $prenom = $user->getFirstName();

        if ($nom && str_contains($text, mb_strtolower($nom))) $totalScore += 5;
        if ($prenom && str_contains($text, mb_strtolower($prenom))) $totalScore += 5;

        // 2. DIPLÔMES (+25 pts)
        $diplomes = ['ingenieur' => 25, 'master' => 20, 'licence' => 15, 'technicien' => 10, 'bac' => 5];
        foreach ($diplomes as $keyword => $points) {
            if (str_contains($text, $keyword)) {
                $totalScore += $points;
                break; 
            }
        }

        // 3. TITRE MÉTIER (+25 pts)
        $jobTitle = mb_strtolower($offre->getTitle());
        if (str_contains($text, $jobTitle)) $totalScore += 25;

        // 4. BONUS STRUCTURE (+15 pts) - Détecte les mots en MAJUSCULES (ex: CGI, STEG)
        preg_match_all('/[A-Z]{3,}/', $offre->getDescription(), $matches);
        foreach (array_unique($matches[0] ?? []) as $stuc) {
            if (str_contains(strtoupper($textRaw), $stuc)) {
                $totalScore += 15;
                break;
            }
        }

        // 5. COMPÉTENCES (+25 pts max)
        $description = mb_strtolower(strip_tags($offre->getDescription()));
        $words = array_filter(explode(' ', str_replace(['.', ',', '!'], ' ', $description)), fn($w) => strlen($w) > 4);
        $uniqueKeywords = array_unique($words);
        if (count($uniqueKeywords) > 0) {
            $matchCount = 0;
            foreach ($uniqueKeywords as $word) {
                if (str_contains($text, $word)) $matchCount++;
            }
            $totalScore += ($matchCount / count($uniqueKeywords)) * 25;
        }

        return min(round($totalScore), 100);
    }
}