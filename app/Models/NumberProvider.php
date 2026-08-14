<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NumberProvider extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'credentials',
        'markup_percent',
        'priority',
        'is_active',
        'last_synced_at',
        'last_test_result',
        'last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'credentials'      => 'encrypted:array',
            'markup_percent'   => 'decimal:4',
            'is_active'        => 'boolean',
            'last_synced_at'   => 'datetime',
            'last_test_result' => 'array',
            'last_tested_at'   => 'datetime',
        ];
    }

    public function numberOrders(): HasMany
    {
        return $this->hasMany(NumberOrder::class);
    }

    public function getApiKey(): string
    {
        return trim((string) ($this->credentials['api_key'] ?? ''));
    }

    public function getBaseUrl(): string
    {
        $baseUrl = $this->credentials['base_url'] ?? match ($this->driver) {
            'pvapins' => 'https://api.pvapins.com/user/api/',
            'smspva'  => 'https://api.smspva.com/',
            'fivesim' => 'https://5sim.net/v1/',
            default   => '',
        };

        $baseUrl = trim((string) $baseUrl);

        if ($this->driver === 'pvapins' && preg_match('#^https?://(www\.)?pvapins\.com/?$#i', $baseUrl)) {
            return 'https://api.pvapins.com/user/api/';
        }

        return $baseUrl === '' ? $baseUrl : rtrim($baseUrl, '/') . '/';
    }

    /** Apply markup to a raw provider cost (USD). 4dp matches what is displayed to users. */
    public function applyMarkup(float $cost): float
    {
        $pct = (float) $this->markup_percent;
        return round($cost * (1 + $pct / 100), 4);
    }
}
