<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/tasks/ai')]
class TaskAiController extends AbstractController
{
    #[Route('/summarize', name: 'task_ai_summarize', methods: ['POST'])]
    public function summarize(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';

        // On récupère la clé via les paramètres du conteneur (plus fiable)
        $apiKey = $this->getParameter('openai_api_key');

        if (!$apiKey || $apiKey === 'TODO') {
            return new JsonResponse(['error' => 'Clé API OpenAI non configurée dans le fichier .env.local'], 500);
        }

        if (strlen($text) < 10) {
            return new JsonResponse(['error' => 'Le texte est trop court pour être résumé.'], 400);
        }

        try {
            $response = $httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant agricole. Résume le texte suivant en une seule phrase courte et claire (max 150 car.).'
                        ],
                        [
                            'role' => 'user',
                            'content' => $text
                        ]
                    ],
                    'temperature' => 0.7,
                ],
                // Optionnel : Désactiver la vérification SSL si on est sur un environnement local capricieux
                'verify_peer' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 429) {
                // Quota dépassé : on renvoie un mock pour ne pas bloquer l'utilisateur
                $mockSummary = "[Simulation] " . (explode('.', $text)[0] ?? $text) . ".";
                return new JsonResponse(['summary' => trim($mockSummary), 'warning' => 'Quota API dépassé, mode simulation activé.']);
            }

            if ($statusCode !== 200) {
                $content = $response->toArray(false);
                $errorMsg = $content['error']['message'] ?? 'Erreur inconnue de l\'API OpenAI';
                return new JsonResponse(['error' => 'Erreur API OpenAI (' . $statusCode . ') : ' . $errorMsg], $statusCode);
            }

            $result = $response->toArray();
            $summary = $result['choices'][0]['message']['content'] ?? 'Résumé non disponible.';

            return new JsonResponse(['summary' => trim($summary)]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur technique : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/translate', name: 'task_ai_translate', methods: ['POST'])]
    public function translate(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        $targetLanguage = $data['target'] ?? 'en';

        $apiKey = $this->getParameter('openai_api_key');

        if (!$apiKey || $apiKey === 'TODO') {
            return new JsonResponse(['error' => 'Clé API OpenAI non configurée.'], 500);
        }

        if (strlen($text) < 2) {
            return new JsonResponse(['error' => 'Le texte est trop court pour être traduit.'], 400);
        }

        try {
            $response = $httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un traducteur professionnel. Traduis le texte suivant en $targetLanguage. Ne renvoie QUE la traduction."
                        ],
                        [
                            'role' => 'user',
                            'content' => $text
                        ]
                    ],
                    'temperature' => 0.3,
                ],
                'verify_peer' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 429) {
                $mockTranslation = "[Simulation-$targetLanguage] " . $text;
                return new JsonResponse(['translation' => $mockTranslation, 'warning' => 'Quota API dépassé, mode simulation activé.']);
            }

            if ($statusCode !== 200) {
                return new JsonResponse(['error' => 'Erreur API OpenAI (' . $statusCode . ')'], $statusCode);
            }

            $result = $response->toArray();
            $translation = $result['choices'][0]['message']['content'] ?? 'Traduction non disponible.';

            return new JsonResponse(['translation' => trim($translation)]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur technique : ' . $e->getMessage()], 500);
        }
    }
}
