<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient, ?string $geminiApiKey = null)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = (string) ($geminiApiKey ?? '');
    }

    /**
     * Assistant chat (fallback when OpenAI quota/limit).
     *
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function chat(array $messages, string $catalog = ''): string
    {
        if ($this->apiKey === '' || trim($this->apiKey) === '') {
            throw new \RuntimeException('Clé API Gemini non configurée.');
        }

        $catalogSection = $catalog
            ? "\n\nCatalogue produits AgriSmart (stock) :\n" . $catalog . "\n\nBase-toi sur ces produits."
            : '';

        $system = 'Tu es un assistant de vente pour AgriSmart, marketplace agricole tunisienne. Réponds en français, concis et pro.' . $catalogSection;
        $conversation = $system . "\n\n";
        foreach ($messages as $m) {
            $role = $m['role'] === 'user' ? 'Utilisateur' : 'Assistant';
            $conversation .= $role . ' : ' . trim($m['content']) . "\n";
        }
        $conversation .= "Assistant : ";

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;
        $response = $this->httpClient->request('POST', $url, [
            'json' => [
                'contents' => [['parts' => [['text' => $conversation]]]],
                'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 350],
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new \RuntimeException('Erreur API Gemini (code ' . $statusCode . ').');
        }
        $data = $response->toArray(false);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return trim($text) ?: 'Je n\'ai pas pu générer une réponse.';
    }

    /**
     * Generate a product description suggestion using Gemini API.
     */
    public function suggestDescription(string $nomProduit, string $categorie): string
    {
        $prompt = sprintf(
            'Tu es un assistant pour une marketplace agricole tunisienne appelée AgriSmart. '
            . 'Génère une description commerciale courte (3-4 phrases max) et attractive en français '
            . 'pour un produit agricole nommé "%s" dans la catégorie "%s". '
            . 'La description doit être directe, professionnelle et mettre en valeur le produit. '
            . 'Ne mets pas de titre, juste le texte de la description.',
            $nomProduit,
            $categorie
        );

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;

        $response = $this->httpClient->request('POST', $url, [
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 200,
                ],
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode === 429) {
            throw new \RuntimeException('429 - Limite de requêtes atteinte. Réessayez dans quelques secondes.');
        }

        if ($statusCode === 400) {
            throw new \RuntimeException('400 - Clé API invalide ou requête incorrecte.');
        }

        if ($statusCode !== 200) {
            throw new \RuntimeException($statusCode . ' - Erreur de l\'API Gemini.');
        }

        $data = $response->toArray(false);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}
