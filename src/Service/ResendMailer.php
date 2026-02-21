<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ResendMailer
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey,
    ) {}

    public function sendEmail(string $to, string $subject, string $htmlContent): void
    {
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

        if ($response->getStatusCode() !== 202) {
            throw new TransportExceptionInterface(
                'Erreur lors de l\'envoi de l\'email via Resend'
            );
        }
    }
}