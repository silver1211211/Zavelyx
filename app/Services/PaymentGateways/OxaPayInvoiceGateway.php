<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OxaPayInvoiceGateway extends OxaPayGateway
{
    // Safe fallback used only when OxaPay's currency catalogue is temporarily unavailable.
    private const FALLBACK_CURRENCIES = [
        ['label' => 'USDT BEP20',   'value' => 'USDT_BEP20', 'pay_currency' => 'USDT', 'network' => 'BEP20', 'enabled' => true],
        ['label' => 'USDT TRC20',   'value' => 'USDT_TRC20', 'pay_currency' => 'USDT', 'network' => 'TRC20', 'enabled' => true],
        ['label' => 'Ethereum',     'value' => 'ETH_ERC20',  'pay_currency' => 'ETH',  'network' => 'ERC20', 'enabled' => true],
        ['label' => 'Bitcoin',      'value' => 'BTC_BITCOIN','pay_currency' => 'BTC',  'network' => 'Bitcoin', 'enabled' => true],
        ['label' => 'Polygon USDT', 'value' => 'USDT_POLYGON','pay_currency' => 'USDT','network' => 'Polygon', 'enabled' => true],
    ];

    // Maps internal currency IDs to OxaPay white-label API params.
    // network: null means the field should be omitted from the request.
    public function getDriver(): string { return 'oxapay_invoice'; }

    public function getAcceptedCoins(): array
    {
        return Cache::remember("oxapay:accepted-coins:{$this->gateway->id}", now()->addHour(), function (): array {
            try {
                $common = Http::timeout(15)->get(self::API . '/v1/common/currencies')->json('data', []);
                $prices = Http::timeout(15)->get(self::API . '/v1/common/prices')->json('data', []);
                $acceptedResponse = Http::withHeaders([
                    'merchant_api_key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(15)->get(self::API . '/v1/payment/accepted-currencies');

                $accepted = $this->acceptedSymbols($acceptedResponse->json('data', []));
                $coins = [];

                foreach ($common as $symbol => $currency) {
                    $symbol = strtoupper((string) $symbol);
                    if (($currency['status'] ?? true) === false || ($accepted && !in_array($symbol, $accepted, true))) {
                        continue;
                    }

                    foreach (($currency['networks'] ?? []) as $networkKey => $network) {
                        $networkName = (string) ($network['network'] ?? $networkKey);
                        $apiNetwork = $this->preferredNetworkKey($networkName, $network['keys'] ?? []);
                        $minimumCrypto = isset($network['deposit_min']) ? (float) $network['deposit_min'] : null;
                        $minimumUsd = $minimumCrypto && isset($prices[$symbol])
                            ? max(1, ceil($minimumCrypto * (float) $prices[$symbol] * 100) / 100)
                            : 1;
                        $coins[] = [
                            'label' => ($currency['name'] ?? $symbol) . ' · ' . ($network['name'] ?? $networkName),
                            'currency_name' => $currency['name'] ?? $symbol,
                            'value' => $symbol . '_' . strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $apiNetwork)),
                            'pay_currency' => $symbol,
                            'network' => $apiNetwork,
                            'network_name' => $network['name'] ?? $networkName,
                            'min_amount' => $minimumCrypto,
                            'min_usd' => $minimumUsd,
                            'confirmations' => isset($network['required_confirmations']) ? (int) $network['required_confirmations'] : null,
                            'enabled' => true,
                        ];
                    }
                }

                return $coins ?: self::FALLBACK_CURRENCIES;
            } catch (\Throwable $e) {
                Log::warning('[OxaPayInvoice] Currency catalogue unavailable', ['error' => $e->getMessage()]);
                return self::FALLBACK_CURRENCIES;
            }
        });
    }

    private function acceptedSymbols(array $data): array
    {
        $items = $data['list'] ?? $data['currencies'] ?? $data;
        $symbols = [];
        foreach ($items as $key => $item) {
            $symbol = is_string($item)
                ? $item
                : (is_array($item) ? ($item['symbol'] ?? $item['currency'] ?? null) : null);
            $symbol ??= is_string($key) ? $key : null;
            if ($symbol) $symbols[] = strtoupper($symbol);
        }
        return array_values(array_unique($symbols));
    }

    private function preferredNetworkKey(string $network, array $keys): string
    {
        $preferred = ['BEP20', 'TRC20', 'ERC20', 'TON', 'BTC'];
        foreach ($preferred as $alias) {
            if (in_array($alias, $keys, true)) return $alias;
        }
        return $network;
    }

    // Creates an OxaPay white-label invoice that returns a direct deposit address.
    // $toCurrency is our internal ID (e.g. "USDT_TRON", "BTC").
    public function createCoinInvoice(
        float  $amount,
        string $currency,
        string $toCurrency,
        string $reference,
        string $description,
        string $returnUrl,
        string $ipnUrl,
        string $email = '',
    ): array {
        $params = collect($this->getAcceptedCoins())->firstWhere('value', $toCurrency);

        if (!$params) {
            return ['success' => false, 'message' => "Unsupported currency: {$toCurrency}"];
        }

        $payload = [
            'amount'         => $amount,
            'currency'       => strtoupper($currency),
            'lifetime'       => 60,
            'fee_paid_by_payer' => 1,
            'under_paid_coverage' => 10,
            'pay_currency'   => $params['pay_currency'],
            'to_currency'    => 'USDT',
            'auto_withdrawal' => false,
            'order_id'       => $reference,
            'description'    => $description,
            'callback_url'   => $ipnUrl,
        ];

        if (!empty($params['network'])) {
            $payload['network'] = $params['network'];
        }

        if (!empty($email)) {
            $payload['email'] = $email;
        }

        Log::info('[OxaPayInvoice] Creating white-label invoice', [
            'reference'    => $reference,
            'amount'       => $amount,
            'pay_currency' => $params['pay_currency'],
            'network'      => $params['network'],
        ]);

        try {
            $response = Http::withHeaders([
                'merchant_api_key' => $this->apiKey,
                'Content-Type'     => 'application/json',
            ])->timeout(20)->post(self::API . '/v1/payment/white-label', $payload);

            Log::info('[OxaPayInvoice] White-label response', [
                'status' => $response->status(),
                'preview' => mb_substr($response->body(), 0, 500),
            ]);

            $data = $response->json();

            if (!$response->successful() || empty($data['data']['track_id'])) {
                $msg = data_get($data, 'error.message')
                    ?: ($data['message'] ?? "OxaPay API error (HTTP {$response->status()})");
                Log::error('[OxaPayInvoice] createCoinInvoice failed', [
                    'status' => $response->status(),
                    'preview' => mb_substr($response->body(), 0, 500),
                ]);
                return ['success' => false, 'message' => $msg];
            }

            $inv       = $data['data'];
            $trackId   = $inv['track_id'] ?? '';
            $address   = $inv['address'] ?? null;
            $payAmount = isset($inv['pay_amount']) ? (float) $inv['pay_amount'] : null;
            $qrCode    = $inv['qr_code'] ?? null;
            $memo      = $inv['memo'] ?? null; // Required for XRP destination tag / TON comment
            $expTs     = $inv['expired_at'] ?? $inv['expire_time'] ?? null;
            $expiresAt = $expTs
                ? Carbon::createFromTimestamp((int) $expTs)
                : now()->addMinutes(60);

            return [
                'success'      => true,
                'track_id'     => $trackId,
                'pay_address'  => $address,
                'pay_amount'   => $payAmount,
                'pay_currency' => $inv['pay_currency'] ?? $params['pay_currency'],
                'network'      => $inv['network']      ?? $params['network'],
                'qr_code_url'  => $qrCode,
                'memo'         => $memo,
                'invoice_url'  => $inv['payment_url']  ?? null, // hosted fallback, may be absent
                'expires_at'   => $expiresAt,
                'raw_response' => $inv,
            ];

        } catch (\Throwable $e) {
            Log::error('[OxaPayInvoice] createCoinInvoice exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not connect to OxaPay. Please try again.'];
        }
    }
}
