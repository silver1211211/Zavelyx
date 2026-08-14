<?php

namespace App\Services;

use App\Contracts\NumberProviderContract;
use App\Models\NumberProvider;
use RuntimeException;

class NumberProviderService
{
    /**
     * Resolve the concrete driver for a given NumberProvider model.
     */
    public function driver(NumberProvider $provider): NumberProviderContract
    {
        $provider->refresh();
        $driver = $this->effectiveDriver($provider);

        return match ($driver) {
            'fivesim' => new FiveSimService($provider->getApiKey(), $provider->getBaseUrl()),
            'pvapins' => new PVAPinsService($provider->getApiKey(), $provider->getBaseUrl()),
            'smspva'  => new SmsPvaService($provider->getApiKey(), $provider->getBaseUrl()),
            default   => throw new RuntimeException("Unknown number provider driver: {$provider->driver}"),
        };
    }

    public function effectiveDriver(NumberProvider $provider): string
    {
        $baseUrl = strtolower($provider->getBaseUrl());
        $name = strtolower($provider->name);

        if (str_contains($baseUrl, 'smspva.com') || str_contains($name, 'smspva') || str_contains($name, 'sms pva')) {
            return 'smspva';
        }

        if (str_contains($baseUrl, '5sim.net') || str_contains($name, '5sim')) {
            return 'fivesim';
        }

        if (str_contains($baseUrl, 'pvapins.com') || str_contains($name, 'pvapins')) {
            return 'pvapins';
        }

        return $provider->driver;
    }

    /**
     * Get the first active provider, ordered by priority.
     */
    public function getActiveProvider(): NumberProvider
    {
        $provider = NumberProvider::where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->first();
        if (!$provider) {
            throw new RuntimeException('No active number provider configured.');
        }
        return $provider;
    }

    /**
     * Get all active providers ordered by priority then id (deterministic).
     *
     * @return NumberProvider[]
     */
    public function getAllActiveProviders(): array
    {
        return NumberProvider::where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Normalize a 5SIM status string to our internal constants.
     */
    public function normalizeStatus(string $raw): string
    {
        return match (strtoupper($raw)) {
            'PENDING'   => 'PENDING',
            'RECEIVED'  => 'RECEIVED',
            'FINISHED'  => 'FINISHED',
            'CANCELLED', 'CANCELED' => 'CANCELLED',
            'BANNED'    => 'BANNED',
            'EXPIRED'   => 'EXPIRED',
            'TIMEOUT'   => 'TIMEOUT',
            default     => 'PENDING',
        };
    }

    /**
     * Extract the first numeric code from an SMS message body.
     */
    public function extractCode(string $text): ?string
    {
        // Match 4–8 digit codes
        if (preg_match('/\b(\d{4,8})\b/', $text, $m)) {
            return $m[1];
        }
        return null;
    }
}
