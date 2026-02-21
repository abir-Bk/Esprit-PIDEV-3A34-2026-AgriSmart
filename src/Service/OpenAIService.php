<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient, ?string $openAiApiKey = null)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = (string) ($openAiApiKey ?? '');
    }

    /**
     * Send a conversation to OpenAI and return the assistant reply.
     *
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function chat(array $messages, string $catalog = ''): string
    {
        if ($this->apiKey === '' || trim($this->apiKey) === '') {
            throw new \RuntimeException('Clé API OpenAI non configurée. Définissez OPENAI_API_KEY dans .env.');
        }

        $catalogSection = $catalog
            ? "\n\nVoici le catalogue actuel des produits disponibles en stock sur AgriSmart :\n" . $catalog
            . "\n\nBasez vos recommandations uniquement sur ces produits réels. Citez le nom exact, la catégorie et le prix."
            : '';

        $systemPrompt = 'Tu es un assistant de vente intelligent pour AgriSmart, une marketplace agricole tunisienne. '
            . 'Ton rôle est d\'aider les utilisateurs à trouver les meilleurs produits agricoles selon leurs besoins. '
            . 'Réponds toujours en français, de manière concise, professionnelle et utile. '
            . 'Si on te demande quelque chose hors contexte agricole, redirige poliment la conversation '
            . 'vers les produits et services disponibles sur AgriSmart.'
            . $catalogSection;

        $fullMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        );

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => $fullMessages,
                'max_tokens' => 350,
                'temperature' => 0.7,
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $apiMessage = null;
            $errorCode = null;
            try {
                $body = $response->toArray(false);
                $apiMessage = $body['error']['message'] ?? null;
                $errorCode = $body['error']['code'] ?? $body['error']['type'] ?? null;
            } catch (\Throwable $e) {
                // Réponse non-JSON (ex. page HTML d'erreur) : on garde seulement le code HTTP
            }

            if ($errorCode === 'insufficient_quota' || $errorCode === 'billing_hard_limit_reached') {
                throw new \RuntimeException('Quota OpenAI épuisé. Veuillez recharger les crédits du compte.');
            }

            if ($statusCode === 429) {
                $detail = $apiMessage ? ' ' . $apiMessage : 'Veuillez réessayer dans une minute.';
                throw new \RuntimeException('Limite de requêtes atteinte.' . $detail);
            }

            if ($statusCode === 401) {
                throw new \RuntimeException('Clé API OpenAI invalide. Vérifiez OPENAI_API_KEY dans .env.');
            }

            $detail = $apiMessage ? ' — ' . $apiMessage : '';
            throw new \RuntimeException('Erreur API OpenAI (code ' . $statusCode . ')' . $detail . '.');
        }

        $data = $response->toArray();

        return $data['choices'][0]['message']['content']
            ?? 'Je suis désolé, je n\'ai pas pu générer une réponse.';
    }
}
