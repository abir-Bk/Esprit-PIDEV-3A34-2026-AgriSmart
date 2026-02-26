<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HuggingFaceService
{
    private string $apiKey;
    private string $model;
    private HttpClientInterface $httpClient;

    public function __construct(ParameterBagInterface $params, HttpClientInterface $httpClient)
    {
        $this->apiKey = $params->get('huggingface_api_key');
        $this->model = $params->get('huggingface_model');
        $this->httpClient = $httpClient;
    }

    /**
     * Generates a recommendation for a task description based on its title and type.
     */
    public function recommendDescription(string $title, string $type): string
    {
        if (empty($title)) {
            return "Veuillez d'abord saisir un titre pour obtenir une recommandation.";
        }

        try {
            $response = $this->httpClient->request('POST', "https://router.huggingface.co/v1/chat/completions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant agricole expert. Réponds uniquement avec la description demandée, de manière concise (max 3 phrases).'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Génère une description professionnelle pour l'intervention suivante :\nTitre : $title\nType : $type"
                        ]
                    ],
                    'max_tokens' => 250,
                    'temperature' => 0.7,
                ],
                'verify_peer' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return "Désolé, l'IA n'est pas disponible pour le moment (Erreur code " . $response->getStatusCode() . ").";
            }

            $result = $response->toArray();

            // Format for /v1/chat/completions
            $text = $result['choices'][0]['message']['content'] ?? 'Recommandation non disponible.';

            return trim($text);
        } catch (\Exception $e) {
            return "Une erreur technique est survenue lors de la génération : " . $e->getMessage();
        }
    }

    /**
     * Translates text into a target language using Hugging Face.
     */
    public function translateText(string $text, string $targetLanguage): string
    {
        if (empty($text)) {
            return "Le texte est vide.";
        }

        try {
            $response = $this->httpClient->request('POST', "https://router.huggingface.co/v1/chat/completions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Tu es un traducteur professionnel. Traduis le texte suivant en $targetLanguage. Ne renvoie QUE la traduction, sans aucun autre texte."
                        ],
                        [
                            'role' => 'user',
                            'content' => $text
                        ]
                    ],
                    'max_tokens' => 1000,
                    'temperature' => 0.3,
                ],
                'verify_peer' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return "Erreur de traduction (Code " . $response->getStatusCode() . ").";
            }

            $result = $response->toArray();
            $translated = $result['choices'][0]['message']['content'] ?? '';

            return trim($translated);
        } catch (\Exception $e) {
            return "Erreur technique de traduction : " . $e->getMessage();
        }
    }

    /**
     * Summarizes text using Hugging Face.
     */
    public function summarizeText(string $text): string
    {
        if (empty($text)) {
            return "Le texte est vide.";
        }

        try {
            $response = $this->httpClient->request('POST', "https://router.huggingface.co/v1/chat/completions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
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
                    'max_tokens' => 150,
                    'temperature' => 0.7,
                ],
                'verify_peer' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return "Erreur de résumé (Code " . $response->getStatusCode() . ").";
            }

            $result = $response->toArray();
            $summary = $result['choices'][0]['message']['content'] ?? '';

            return trim($summary);
        } catch (\Exception $e) {
            return "Erreur technique de résumé : " . $e->getMessage();
        }
    }
}
