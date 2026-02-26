<?php

require_once 'vendor/autoload.php';

use App\Service\PredictionService;

$env = file_get_contents('.env');
preg_match('/MISTRAL_API_KEY=(.*)/', $env, $mistralMatches);
preg_match('/HUGGINGFACE_TOKEN=(.*)/', $env, $hfMatches);

$mistralKey = trim($mistralMatches[1] ?? 'NOT_FOUND');
$hfKey = trim($hfMatches[1] ?? 'NOT_FOUND');

$service = new PredictionService($hfKey, $mistralKey);

$context = "Données utilisateur :\n";
$context .= "- Parcelle: Nord-Ouest, Surface: 2.5ha, Sol: Argileux\n";
$context .= "  * Culture: Blé (Spunta), Statut: En croissance, Plantation: 01/01/2026\n";
$context .= "\nStocks de ressources :\n";
$context .= "- Engrais NPK: 15.0 Sacs\n";
$context .= "- Savon Noir: 2.0 Litres\n";

$questions = [
    "Quel est le rendement prévu pour ma parcelle de blé ?",
    "Il y a des insectes noirs sur mes tomates, que faire ?",
    "Combien de sacs d'engrais me faut-il pour mes 2.5 hectares ?",
    "Vérifie mon stock actuel d'engrais svp."
];

$results = "--- TEST DES SCÉNARIOS UTILISATEUR ---\n\n";

foreach ($questions as $q) {
    $results .= "Question: $q\n";
    $response = $service->generateChatResponse($q, $context);
    $results .= "Réponse: $response\n";
    $results .= "--------------------------------------\n\n";
}

file_put_contents('test_scenarios.txt', $results);
echo "Résultats sauvegardés dans test_scenarios.txt\n";
