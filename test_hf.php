<?php

$apiKey = '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';
$model = 'mistralai/Mistral-7B-Instruct-v0.3';
$url = "https://router.huggingface.co/v1/chat/completions";

$data = json_encode([
    'model' => 'meta-llama/Llama-3.2-3B-Instruct',
    'messages' => [['role' => 'user', 'content' => 'Hello']]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $apiKey,
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $httpCode\n";
file_put_contents('router_models.json', $result);
echo "Full response saved to router_models.json\n";
