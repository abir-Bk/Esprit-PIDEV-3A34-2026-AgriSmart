<?php
namespace App\Service;

use GeoIp2\Database\Reader;

class GeoIpService
{
    private Reader $reader;

    public function __construct(string $dbPath)
    {
        $this->reader = new Reader($dbPath);
    }

    public function getCountryCode(string $ip): ?string
    {
        try {
            $record = $this->reader->country($ip);
            return $record->country->isoCode; // returns 'US', 'TN', etc.
        } catch (\Exception $e) {
            return null; // fallback if IP cannot be resolved
        }
    }
}