<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OxaPayInvoiceGateway extends OxaPayGateway
{
    // Display labels + internal IDs for the coin selector.
    private const CURRENCIES = [
        ['label' => 'USDT BEP20',   'value' => 'USDT_BEP20', 'enabled' => true],
        ['label' => 'USDT TRC20',   'value' => 'USDT_TRC20', 'enabled' => false],
        ['label' => 'Ethereum',     'value' => 'ETH',        'enabled' => false],
        ['label' => 'Bitcoin',      'value' => 'BTC',        'enabled' => false],
        ['label' => 'Polygon USDT', 'value' => 'USDT_POLYGON','enabled' => false],
    ];

    // Maps internal currency IDs to OxaPay white-label API params.
    // network: null means the field should be omitted from the request.
    private const CURRENCY_PARAMS = [
        'USDT_BEP20' => ['pay_currency' => 'USDT', 'network' => 'BEP20'],
    ];

    public function getDriver(): string { return 'oxapay_invoice'; }

    public function getAcceptedCoins(): array
    {
        return self::CURRENCIES;
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
        $params = self::CURRENCY_PARAMS[$toCurrency] ?? null;

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
                $msg = $data['message'] ?? "OxaPay API error (HTTP {$response->status()})";
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
