<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ResendMailer
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey,
    ) {}

 public function sendEmail(string $to, string $subject, string $htmlContent): void
{
    try {
        $response = $this->client->request('POST', 'https://api.resend.com/emails', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'from' => $_ENV['MAILER_FROM'] ?? 'AgriSmart <sandbox@resend.dev>',
                'to' => [$to],
                'subject' => $subject,
                'html' => $htmlContent,
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200 && $statusCode !== 202) {
            // just log instead of breaking login
            dump('Email failed: ' . $statusCode);
        }

    } catch (\Throwable $e) {
        // NEVER break authentication flow
        dump('Mailer error: ' . $e->getMessage());
    }
}}