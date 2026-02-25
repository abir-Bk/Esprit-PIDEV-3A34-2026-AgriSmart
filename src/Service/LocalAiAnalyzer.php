<?php

namespace App\Service;

class LocalAiAnalyzer
{
    /**
     * Keywords associated with different priority levels.
     */
    private const HIGH_PRIORITY_KEYWORDS = [
        'urgent',
        'immédiatement',
        'critique',
        'panne',
        'cassé',
        'fuite',
        'danger',
        'mort',
        'incendie',
        'maladie',
        'grave',
        'bloqué',
        'arrêt',
        'urgence',
        'hs',
        'problème grave',
        'urgentissime'
    ];

    private const MEDIUM_PRIORITY_KEYWORDS = [
        'important',
        'rapide',
        'prévision',
        'vérifier',
        'attention',
        'besoin',
        'maintenance',
        'problème',
        'anomalie'
    ];

    /**
     * Analyzes text (title + description) and returns a priority label.
     */
    public function analyzePriority(string $title, string $description = ''): string
    {
        $text = mb_strtolower($title . ' ' . $description);

        $highCount = 0;
        foreach (self::HIGH_PRIORITY_KEYWORDS as $keyword) {
            if (str_contains($text, $keyword)) {
                $highCount++;
            }
        }

        if ($highCount > 0) {
            return 'high';
        }

        $mediumCount = 0;
        foreach (self::MEDIUM_PRIORITY_KEYWORDS as $keyword) {
            if (str_contains($text, $keyword)) {
                $mediumCount++;
            }
        }

        if ($mediumCount > 0) {
            return 'medium';
        }

        return 'low';
    }
}
