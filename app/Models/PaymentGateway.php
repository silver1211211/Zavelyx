<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'is_active',
        'is_default',
        'api_key',
        'ipn_secret',
        'extra_config',
        'fee_percent',
        'min_amount',
        'max_amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'is_default'   => 'boolean',
            'fee_percent'  => 'decimal:2',
            'min_amount'   => 'decimal:2',
            'max_amount'   => 'decimal:2',
            'extra_config' => 'array',
        ];
    }

    // ── Encrypted field accessors ──────────────────────────────────────────────

    public function getApiKeyDecrypted(): string
    {
        if (empty($this->api_key)) {
            return in_array($this->driver, ['oxapay', 'oxapay_invoice'], true)
                ? (string) env('OXAPAY_MERCHANT_API_KEY', '')
                : '';
        }
        try { return Crypt::decryptString($this->api_key); } catch (\Throwable) { return ''; }
    }

    public function getIpnSecretDecrypted(): string
    {
        if (empty($this->ipn_secret)) return '';
        try { return Crypt::decryptString($this->ipn_secret); } catch (\Throwable) { return ''; }
    }

    public function setApiKeyEncrypted(string $key): void
    {
        $this->update(['api_key' => empty($key) ? null : Crypt::encryptString($key)]);
    }

    public function setIpnSecretEncrypted(string $secret): void
    {
        $this->update(['ipn_secret' => empty($secret) ? null : Crypt::encryptString($secret)]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isConfigured(): bool
    {
        // Both OxaPay variants use only the merchant_api_key — no separate secret.
        if (in_array($this->driver, ['oxapay', 'oxapay_invoice'], true)) {
            return $this->getApiKeyDecrypted() !== '';
        }

        return !empty($this->api_key) && !empty($this->ipn_secret);
    }

    public function isSandbox(): bool
    {
        return (bool) ($this->extra_config['sandbox'] ?? false);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
