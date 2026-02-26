<?php

namespace App\Service;

class PredictionService
{
    private string $hfApiKey;
    private string $mistralApiKey;

    public function __construct(string $huggingfaceToken, string $mistralApiKey)
    {
        $this->hfApiKey = trim(str_replace(['"', "'"], '', $huggingfaceToken));
        $this->mistralApiKey = trim(str_replace(['"', "'"], '', $mistralApiKey));
    }

    public function predict(float $surface, float $totalRessources, string $typeCulture): string
    {
        $url = "https://api.mistral.ai/v1/chat/completions";

        $prompt = "Predict the yield in tons for $surface ha of $typeCulture with $totalRessources units of fertilizer. Respond with ONLY the number.";

        $data = [
            "model" => "mistral-small-latest",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "max_tokens" => 10,
            "temperature" => 0.1
        ];

        return $this->callMistral($url, $data, true);
    }

    public function generateChatResponse(string $userMessage, string $context = ""): string
    {
        $url = "https://router.huggingface.co/v1/chat/completions";

        $systemPrompt = "Tu es AgriChat, un assistant agronomique expert. "
                      . "RÈGLE D'OR : Tes réponses doivent être COURTES, CLAIRES et DIRECTES. "
                      . "Utilise des listes à puces si nécessaire. Évite les longs paragraphes.\n\n"
                      . "TES CAPACITÉS :\n"
                      . "1. Données locales : Réponse précise sur les parcelles/stocks. Exemple : 'Rendement prévu : 12.5t. Conseil : Drainage.'\n"
                      . "2. Diagnostic : Identifie le problème et donne 1 ou 2 solutions immédiates.\n"
                      . "3. Calculateur : Donne le chiffre exact suivi d'une courte explication.\n"
                      . "4. Sécurité : Uniquement agriculture/météo.\n\n"
                      . "CONTEXTE ACTUEL DE L'UTILISATEUR :\n" . $context;

        $data = [
            "model" => "meta-llama/Llama-3.2-3B-Instruct",
            "messages" => [
                ["role" => "system", "content" => $systemPrompt],
                ["role" => "user", "content" => $userMessage]
            ],
            "max_tokens" => 250,
            "temperature" => 0.1 
        ];

        return $this->callHuggingFace($url, $data, false);
    }

    /**
     * Une petite fonction privée pour éviter de répéter le code CURL deux fois
     */
    /**
     * Une petite fonction privée pour éviter de répéter le code CURL deux fois
     */
    private function callHuggingFace(string $url, array $data, bool $isPrediction): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->hfApiKey, 
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $response = json_decode($result, true);
            $content = $response['choices'][0]['message']['content'] ?? "";

            if ($isPrediction) {
                if (preg_match('/[0-9]+([,.][0-9]+)?/', $content, $matches)) {
                    return $matches[0] . " Tonnes ";
                }
            } else {
                return trim($content);
            }
        }

        return $isPrediction ? "Calcul local" : "Désolé, service indisponible.";
    }

    private function callMistral(string $url, array $data, bool $isPrediction): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->mistralApiKey, 
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $response = json_decode($result, true);
            $content = $response['choices'][0]['message']['content'] ?? "";

            if (preg_match('/[0-9]+([,.][0-9]+)?/', $content, $matches)) {
                return $matches[0] . " Tonnes ";
            }
        }

        return "Calcul local";
    }
}