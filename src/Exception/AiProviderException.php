<?php

namespace App\Exception;

class AiProviderException extends \RuntimeException
{
    public function __construct(
        private string $provider,
        private string $kind,
        private string $userMessage,
        private int $statusCode = 503,
        ?string $providerMessage = null
    ) {
        parent::__construct($providerMessage ?? $userMessage);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
