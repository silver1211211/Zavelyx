<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paymento crypto payment gateway driver.
 * Docs: https://paymento.io/crypto-payment-api/
 *
 * Flow:
 *  1. POST https://api.paymento.io/v1/payment/request → receive token in $body['body']
 *  2. Redirect user to https://app.paymento.io/gateway?token=TOKEN
 *  3. User picks coin/network on Paymento hosted page
 *  4. Paymento POSTs IPN to our /api/payments/paymento/ipn
 *  5. We verify HMAC-SHA256 of raw body against X-HMAC-SHA256-SIGNATURE header
 *  6. Credit wallet on OrderStatus = 7 (Paid)
 */
class PaymentoGateway implements GatewayContract
{
    private const API     = 'https://api.paymento.io';
    private const GATEWAY = 'https://app.paymento.io/gateway';

    private string $apiKey;
    private string $secretKey;

    public function __construct(private readonly PaymentGateway $gateway)
    {
        $this->apiKey    = $gateway->getApiKeyDecrypted();
        $this->secretKey = $gateway->getIpnSecretDecrypted();
    }

    public function getDriver(): string { return 'paymento'; }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    // ── Create payment ────────────────────────────────────────────────────────

    public function createInvoice(
        float  $amount,
        string $currency,
        string $reference,
        string $description,
        string $successUrl,
        string $cancelUrl,
        string $ipnUrl,
    ): array {
        $payload = [
            'Speed'          => 0,                         // 0 = high speed (mempool confirmation)
            'fiatAmount'     => (string) $amount,          // must be string per API docs
            'fiatCurrency'   => strtoupper($currency),     // e.g. "USD"
            'ReturnUrl'      => $successUrl,
            'CallbackUrl'    => $ipnUrl,
            'orderId'        => $reference,
            'additionalData' => [['key' => 'description', 'value' => $description]],
        ];

        Log::info('[Paymento] Creating payment', [
            'reference' => $reference,
            'amount'    => $amount,
            'currency'  => strtoupper($currency),
            'ipn_url'   => $ipnUrl,
        ]);

        try {
            $response = Http::withHeaders([
                'Api-key'      => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'text/plain',
            ])->timeout(20)->post(self::API . '/v1/payment/request', $payload);

            Log::info('[Paymento] API response', [
                'http_status' => $response->status(),
                'body'        => $response->body(),
            ]);

            if ($response->successful()) {
                $data  = $response->json();

                // Paymento may return HTTP 200 with success:false and a message
                if (isset($data['success']) && $data['success'] === false) {
                    $msg = $data['message'] ?? 'Paymento rejected the request.';
                    Log::error('[Paymento] API returned success:false', [
                        'message' => $msg,
                        'body'    => $response->body(),
                    ]);
                    return ['success' => false, 'message' => $msg];
                }

                // Token is in $data['body'] per Paymento docs
                $token = $data['body'] ?? null;

                if (empty($token)) {
                    Log::error('[Paymento] No token in response', ['body' => $response->body()]);
                    return ['success' => false, 'message' => 'Paymento returned no payment token.'];
                }

                return [
                    'success'            => true,
                    'invoice_url'        => self::GATEWAY . '?token=' . $token,
                    'gateway_invoice_id' => $token,
                ];
            }

            $errorBody = $response->json() ?? [];
            $msg = $errorBody['message']
                ?? $errorBody['error']
                ?? $errorBody['msg']
                ?? "Paymento API error (HTTP {$response->status()})";

            Log::error('[Paymento] createInvoice failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['success' => false, 'message' => $msg];

        } catch (\Throwable $e) {
            Log::error('[Paymento] createInvoice exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => 'Could not connect to Paymento. Please try again.'];
        }
    }

    // ── IPN signature verification ────────────────────────────────────────────

    /**
     * Paymento sends HMAC-SHA256 of the raw POST body using the secret key.
     * The signature header is X-HMAC-SHA256-SIGNATURE (uppercase hex).
     */
    public function verifyCallback(string $rawBody, string $signature): bool
    {
        if (empty($this->secretKey)) {
            Log::warning('[Paymento IPN] No secret key configured — skipping signature check');
            return true;
        }

        if (empty($signature)) {
            return false;
        }

        $computed = strtoupper(hash_hmac('sha256', $rawBody, $this->secretKey));

        return hash_equals($computed, strtoupper($signature));
    }

    // ── Query payment status ──────────────────────────────────────────────────

    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Api-key' => $this->apiKey,
                'Accept'  => 'application/json',
            ])->timeout(10)->get(self::API . '/v1/payment/' . $paymentId);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status'  => self::mapStatus((string) ($data['OrderStatus'] ?? $data['status'] ?? 'unknown')),
                    'data'    => $data,
                ];
            }

            return ['success' => false, 'status' => 'unknown', 'data' => []];

        } catch (\Throwable $e) {
            Log::error('[Paymento] getPaymentStatus exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'status' => 'unknown', 'data' => []];
        }
    }

    // ── Map Paymento OrderStatus → internal status ────────────────────────────

    /**
     * Paymento sends numeric OrderStatus in callbacks.
     * 7 = Paid/confirmed. Also handles string statuses for future-proofing.
     */
    public static function mapStatus(string $status): string
    {
        // Numeric status codes
        if (is_numeric($status)) {
            return match ((int) $status) {
                7       => 'finished',   // Paid
                1, 2    => 'waiting',    // Created / Pending
                3, 4, 5 => 'confirming', // Processing
                6       => 'confirming', // Partially paid
                8, 9    => 'expired',    // Expired / Cancelled
                10      => 'refunded',   // Refunded
                default => 'waiting',
            };
        }

        // String status fallback
        return match (strtolower($status)) {
            'completed', 'paid', 'success', 'confirmed' => 'finished',
            'pending', 'waiting', 'created'              => 'waiting',
            'confirming', 'processing', 'received'       => 'confirming',
            'failed', 'error'                            => 'failed',
            'expired', 'cancelled', 'canceled'           => 'expired',
            'refunded', 'reversed'                       => 'refunded',
            default                                      => 'waiting',
        };
    }
}
