<?php

require_once 'vendor/autoload.php';

use App\Service\PredictionService;

// Load .env keys manually for testing
$env = file_get_contents('.env');
preg_match('/MISTRAL_API_KEY=(.*)/', $env, $mistralMatches);
preg_match('/HUGGINGFACE_TOKEN=(.*)/', $env, $hfMatches);

$mistralKey = trim($mistralMatches[1] ?? 'NOT_FOUND');
$hfKey = trim($hfMatches[1] ?? 'NOT_FOUND');

echo "Mistral Key found: " . ($mistralKey !== 'NOT_FOUND' ? "Yes" : "No") . "\n";
echo "HF Key found: " . ($hfKey !== 'NOT_FOUND' ? "Yes" : "No") . "\n";

$service = new PredictionService($hfKey, $mistralKey);

$output = "--- Pred (Mistral) ---\n";
$pred = $service->predict(1.5, 200, "Blé");
$output .= "Result: $pred\n";

$output .= "\n--- Chat (HF) With Context ---\n";
$context = "L'utilisateur a une parcelle de 5ha de Blé (variété Spunta) en croissance.";
$chat = $service->generateChatResponse("Parle moi de ma parcelle", $context);
$output .= "Result: " . substr($chat, 0, 150) . "...\n";

$output .= "\n--- Chat (HF) Off-topic ---\n";
$chat = $service->generateChatResponse("Qui est le président de la France ?");
$output .= "Result: " . $chat . "\n";

file_put_contents('hybrid_results.txt', $output);
echo "Results saved to hybrid_results.txt\n";
