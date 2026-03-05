<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateService
{
    /** @var array<string, array<string, float>> */
    private array $ratesByBase = [];
    /** @var array<string, \DateTimeImmutable|null> */
    private array $updatedAtByBase = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $apiUrl = 'https://open.er-api.com/v6/latest/',
        private readonly int $cacheTtlSeconds = 21600,
    ) {
    }

    public function convert(float $amount, string $toCurrency, string $fromCurrency = 'TND'): ?float
    {
        $fromCurrency = strtoupper(trim($fromCurrency));
        $toCurrency = strtoupper(trim($toCurrency));

        if ($fromCurrency === '' || $toCurrency === '') {
            return null;
        }

        if ($fromCurrency === $toCurrency) {
            return round($amount, 2);
        }

        $rates = $this->getRates($fromCurrency);
        if ($rates === null || !isset($rates[$toCurrency])) {
            return null;
        }

        return round($amount * (float) $rates[$toCurrency], 2);
    }

    /** @return array<string, float>|null */
    public function getRates(string $baseCurrency = 'TND'): ?array
    {
        $baseCurrency = strtoupper(trim($baseCurrency));
        if ($baseCurrency === '') {
            return null;
        }

        if (isset($this->ratesByBase[$baseCurrency])) {
            return $this->ratesByBase[$baseCurrency];
        }

        $cacheKey = 'exchange_rates_' . strtolower($baseCurrency);

        /** @var array{rates:array<string,float>,updatedAt:?\DateTimeImmutable}|null $payload */
        $payload = $this->cache->get($cacheKey, function (ItemInterface $item) use ($baseCurrency): ?array {
            $item->expiresAfter($this->cacheTtlSeconds);

            return $this->fetchRates($baseCurrency);
        });

        if (!is_array($payload) || !isset($payload['rates'])) {
            return null;
        }

        $this->ratesByBase[$baseCurrency] = $payload['rates'];
        $this->updatedAtByBase[$baseCurrency] = $payload['updatedAt'] ?? null;

        return $this->ratesByBase[$baseCurrency];
    }

    public function getUpdatedAt(string $baseCurrency = 'TND'): ?\DateTimeImmutable
    {
        $baseCurrency = strtoupper(trim($baseCurrency));
        if ($baseCurrency === '') {
            return null;
        }

        if (!array_key_exists($baseCurrency, $this->updatedAtByBase)) {
            $this->getRates($baseCurrency);
        }

        $updatedAt = $this->updatedAtByBase[$baseCurrency] ?? null;

        return $updatedAt instanceof \DateTimeImmutable ? $updatedAt : null;
    }

    /**
     * @return array{rates:array<string,float>,updatedAt:?\DateTimeImmutable}|null
     */
    private function fetchRates(string $baseCurrency): ?array
    {
        $endpoint = rtrim($this->apiUrl, '/') . '/' . $baseCurrency;

        try {
            $response = $this->httpClient->request('GET', $endpoint, [
                'timeout' => 8,
                'max_duration' => 10,
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('Exchange rate API transport error', [
                'base' => $baseCurrency,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (200 !== $response->getStatusCode()) {
            $this->logger->warning('Exchange rate API non-200 status', [
                'base' => $baseCurrency,
                'status' => $response->getStatusCode(),
            ]);

            return null;
        }

        $data = $response->toArray(false);
        if (!isset($data['rates']) || !is_array($data['rates'])) {
            return null;
        }

        $rates = [];
        foreach ($data['rates'] as $currency => $value) {
            if (!is_string($currency) || !is_numeric($value)) {
                continue;
            }

            $rates[strtoupper($currency)] = (float) $value;
        }

        if ($rates === []) {
            return null;
        }

        $updatedAt = null;
        if (isset($data['time_last_update_unix']) && is_numeric($data['time_last_update_unix'])) {
            $updatedAt = (new \DateTimeImmutable())->setTimestamp((int) $data['time_last_update_unix']);
        } elseif (isset($data['time_last_update_utc']) && is_string($data['time_last_update_utc'])) {
            try {
                $updatedAt = new \DateTimeImmutable($data['time_last_update_utc']);
            } catch (\Throwable) {
                $updatedAt = null;
            }
        }

        return [
            'rates' => $rates,
            'updatedAt' => $updatedAt,
        ];
    }
}
