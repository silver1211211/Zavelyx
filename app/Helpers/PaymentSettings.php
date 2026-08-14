<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class PaymentSettings
{
    private static array $COIN_DEFAULTS = ['btc', 'eth', 'usdt', 'usdc', 'bnb', 'ltc', 'trx', 'sol', 'doge', 'xrp'];

    // ── Encrypted fields ───────────────────────────────────────────────────────

    public static function getApiKey(): string
    {
        $val = Setting::get('payment.nowpayments.api_key', '');
        if (empty($val)) return '';
        try { return Crypt::decryptString($val); } catch (\Throwable) { return ''; }
    }

    public static function setApiKey(string $key): void
    {
        Setting::set('payment.nowpayments.api_key', empty($key) ? '' : Crypt::encryptString($key));
    }

    public static function getIpnSecret(): string
    {
        $val = Setting::get('payment.nowpayments.ipn_secret', '');
        if (empty($val)) return '';
        try { return Crypt::decryptString($val); } catch (\Throwable) { return ''; }
    }

    public static function setIpnSecret(string $secret): void
    {
        Setting::set('payment.nowpayments.ipn_secret', empty($secret) ? '' : Crypt::encryptString($secret));
    }

    // ── Plain settings ─────────────────────────────────────────────────────────

    public static function isEnabled(): bool
    {
        return Setting::get('payment.nowpayments.enabled', '1') === '1';
    }

    public static function isSandbox(): bool
    {
        return Setting::get('payment.nowpayments.sandbox', '0') === '1';
    }

    public static function isMaintenanceMode(): bool
    {
        return Setting::get('payment.deposit.maintenance', '0') === '1';
    }

    public static function getMinDeposit(): float
    {
        return (float) Setting::get('payment.nowpayments.min_deposit', '5');
    }

    public static function getMaxDeposit(): float
    {
        return (float) Setting::get('payment.nowpayments.max_deposit', '10000');
    }

    public static function getFeePercent(): float
    {
        return (float) Setting::get('payment.nowpayments.fee_percent', '0');
    }

    public static function getSupportedCoins(): array
    {
        $raw = Setting::get('payment.nowpayments.supported_coins', null);
        if (!$raw) return self::$COIN_DEFAULTS;
        $coins = json_decode($raw, true);
        return (is_array($coins) && count($coins) > 0) ? $coins : self::$COIN_DEFAULTS;
    }

    public static function setSupportedCoins(array $coins): void
    {
        Setting::set('payment.nowpayments.supported_coins', json_encode(array_values($coins)));
    }

    // ── All settings for admin UI ──────────────────────────────────────────────

    public static function all(): array
    {
        return [
            'enabled'         => self::isEnabled(),
            'sandbox'         => self::isSandbox(),
            'maintenance'     => self::isMaintenanceMode(),
            'min_deposit'     => self::getMinDeposit(),
            'max_deposit'     => self::getMaxDeposit(),
            'fee_percent'     => self::getFeePercent(),
            'supported_coins' => self::getSupportedCoins(),
            'has_api_key'     => !empty(self::getApiKey()),
            'has_ipn_secret'  => !empty(self::getIpnSecret()),
        ];
    }
}
