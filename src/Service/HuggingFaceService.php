<?php

namespace App\Service;

use App\Exception\AiProviderException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private string $apiKey;
    private string $model;
    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        ?string $huggingFaceApiKey = null,
        ?string $huggingFaceModel = null
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = (string) ($huggingFaceApiKey ?? '');
        $this->model = trim((string) ($huggingFaceModel ?? '')) ?: 'Qwen/Qwen2.5-7B-Instruct';
    }

    /**
     * Assistant chat.
     *
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function chat(array $messages, string $catalog = ''): string
    {
        if ($this->apiKey === '' || trim($this->apiKey) === '') {
            throw new AiProviderException(
                provider: 'huggingface',
                kind: 'config',
                userMessage: 'Service Hugging Face non configuré.',
                statusCode: 500
            );
        }

        $catalogSection = $catalog
            ? "\n\nVoici le catalogue actuel des produits disponibles en stock sur AgriSmart :\n" . $catalog
            . "\n\nBasez vos recommandations uniquement sur ces produits réels. Citez le nom exact, la catégorie, le prix et le lien (champ Lien) du produit recommandé."
            : '';

        $systemPrompt = 'Tu es un assistant de vente intelligent pour AgriSmart, une marketplace agricole tunisienne. '
            . 'Ton rôle est d\'aider les utilisateurs à trouver les meilleurs produits agricoles selon leurs besoins. '
            . 'Réponds toujours en français, de manière concise, professionnelle et utile. '
            . 'Si on te demande quelque chose hors contexte agricole, redirige poliment la conversation '
            . 'vers les produits et services disponibles sur AgriSmart. '
            . 'Quand tu proposes un produit, ajoute toujours un lien cliquable vers ce produit.'
            . $catalogSection;

        $fullMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        );

        try {
            $response = $this->httpClient->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'timeout' => 12,
                'max_duration' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => $fullMessages,
                    'max_tokens' => 350,
                    'temperature' => 0.7,
                ],
            ]);
        } catch (TransportExceptionInterface $e) {
            throw new AiProviderException(
                provider: 'huggingface',
                kind: 'api_error',
                userMessage: 'Hugging Face ne répond pas à temps. Vérifiez la connexion réseau et réessayez.',
                statusCode: 503,
                providerMessage: $e->getMessage()
            );
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $apiMessage = null;
            try {
                $body = $response->toArray(false);
                $apiMessage = $body['error']['message'] ?? $body['error'] ?? null;
            } catch (\Throwable $e) {
            }

            if ($statusCode === 429) {
                throw new AiProviderException(
                    provider: 'huggingface',
                    kind: 'quota',
                    userMessage: 'Quota Hugging Face atteint. Réessayez plus tard ou augmentez votre plan.',
                    statusCode: 429,
                    providerMessage: is_string($apiMessage) ? $apiMessage : null
                );
            }

            if ($statusCode === 401 || $statusCode === 403) {
                throw new AiProviderException(
                    provider: 'huggingface',
                    kind: 'auth',
                    userMessage: 'Clé API Hugging Face invalide ou non autorisée.',
                    statusCode: 401,
                    providerMessage: is_string($apiMessage) ? $apiMessage : null
                );
            }

            throw new AiProviderException(
                provider: 'huggingface',
                kind: 'api_error',
                userMessage: 'Erreur API Hugging Face (code ' . $statusCode . ').',
                statusCode: 503,
                providerMessage: is_string($apiMessage) ? $apiMessage : null
            );
        }

        $data = $response->toArray(false);

        return $data['choices'][0]['message']['content']
            ?? 'Je suis désolé, je n\'ai pas pu générer une réponse.';
    }

    /**
     * Generate a product description suggestion using Hugging Face Inference API.
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

        return $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);
    }
}
