<?php

namespace App\Twig;

use App\Service\ExchangeRateService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ExchangeRateExtension extends AbstractExtension
{
    public function __construct(private readonly ExchangeRateService $exchangeRateService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('fx_convert', [$this, 'convert']),
            new TwigFunction('fx_updated_at', [$this, 'updatedAt']),
        ];
    }

    public function convert(float $amount, string $toCurrency, string $fromCurrency = 'TND'): ?float
    {
        return $this->exchangeRateService->convert($amount, $toCurrency, $fromCurrency);
    }

    public function updatedAt(string $baseCurrency = 'TND'): ?\DateTimeImmutable
    {
        return $this->exchangeRateService->getUpdatedAt($baseCurrency);
    }
}
