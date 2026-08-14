<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OxaPay crypto payment gateway driver.
 * Docs: https://docs.oxapay.com/
 *
 * Flow:
 *  1. POST https://api.oxapay.com/v1/payment/invoice  → receive payment_url + track_id
 *  2. Redirect user to payment_url (OxaPay hosted checkout)
 *  3. User selects coin/network on OxaPay page
 *  4. OxaPay POSTs webhook to our /api/payments/oxapay/webhook
 *  5. We verify HMAC-SHA512 of raw body against HMAC header using merchant_api_key
 *  6. Credit wallet on status "Paid"
 */
class OxaPayGateway implements GatewayContract
{
    protected const API = 'https://api.oxapay.com';

    protected string $apiKey;
    protected bool   $sandbox;

    public function __construct(protected readonly PaymentGateway $gateway)
    {
        $this->apiKey  = $gateway->getApiKeyDecrypted();
        $this->sandbox = $gateway->isSandbox();
    }

    public function getDriver(): string { return 'oxapay'; }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    // ── Create invoice ─────────────────────────────────────────────────────────

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
            'amount'       => $amount,
            'currency'     => strtoupper($currency),
            'lifetime'     => 60,
            'callback_url' => $ipnUrl,
            'return_url'   => $successUrl,
            'order_id'     => $reference,
            'description'  => $description,
        ];

        if ($this->sandbox) {
            $payload['sandbox'] = true;
        }

        Log::info('[OxaPay] Creating invoice', [
            'reference' => $reference,
            'amount'    => $amount,
            'currency'  => strtoupper($currency),
            'ipn_url'   => $ipnUrl,
        ]);

        try {
            $response = Http::withHeaders([
                'merchant_api_key' => $this->apiKey,
                'Content-Type'     => 'application/json',
            ])->timeout(20)->post(self::API . '/v1/payment/invoice', $payload);

            Log::info('[OxaPay] API response', [
                'http_status' => $response->status(),
                'body'        => $response->body(),
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['data']['payment_url'])) {
                return [
                    'success'            => true,
                    'invoice_url'        => $data['data']['payment_url'],
                    'gateway_invoice_id' => $data['data']['track_id'] ?? '',
                ];
            }

            $msg = $data['message']
                ?? ($data['error']['message'] ?? null)
                ?? "OxaPay API error (HTTP {$response->status()})";

            Log::error('[OxaPay] createInvoice failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['success' => false, 'message' => $msg];

        } catch (\Throwable $e) {
            Log::error('[OxaPay] createInvoice exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => 'Could not connect to OxaPay. Please try again.'];
        }
    }

    // ── Webhook signature verification ─────────────────────────────────────────

    /**
     * OxaPay sends HMAC-SHA512 of raw POST body using the merchant_api_key.
     * The signature is in the "HMAC" request header.
     */
    public function verifyCallback(string $rawBody, string $signature): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('[OxaPay] No API key configured — skipping signature check');
            return true;
        }

        if (empty($signature)) {
            return false;
        }

        $computed = hash_hmac('sha512', $rawBody, $this->apiKey);

        return hash_equals($computed, strtolower($signature));
    }

    // ── Query payment status ───────────────────────────────────────────────────

    /**
     * Calls OxaPay's inquiry endpoint to get the current payment status.
     * Endpoint: POST /v1/payment/inquiry  (NOT a GET — the old GET route does not exist)
     * OxaPay returns result:100 for success; status values are PascalCase ("Paid", "Waiting", …).
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'merchant_api_key' => $this->apiKey,
                'Content-Type'     => 'application/json',
            ])->timeout(15)->post(self::API . '/v1/payment/inquiry', [
                'trackId' => $paymentId,
            ]);

            $data = $response->json();

            Log::info('[OxaPay] getPaymentStatus response', [
                'payment_id' => $paymentId,
                'http_status'=> $response->status(),
                'result'     => $data['result'] ?? null,
                'status'     => $data['data']['status'] ?? null,
            ]);

            // OxaPay result code 100 = success; anything else is an error.
            if (!$response->successful() || ($data['result'] ?? null) !== 100) {
                Log::warning('[OxaPay] getPaymentStatus non-100 result', [
                    'payment_id' => $paymentId,
                    'result'     => $data['result'] ?? null,
                    'message'    => $data['message'] ?? null,
                ]);
                return ['success' => false, 'status' => 'unknown', 'data' => []];
            }

            $status = $data['data']['status'] ?? 'unknown';
            return [
                'success' => true,
                'status'  => self::mapStatus($status),
                'data'    => $data['data'] ?? [],
            ];

        } catch (\Throwable $e) {
            Log::error('[OxaPay] getPaymentStatus exception', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
            return ['success' => false, 'status' => 'unknown', 'data' => []];
        }
    }

    // ── Map OxaPay status → internal status ───────────────────────────────────

    /**
     * OxaPay statuses:
     *   Waiting  — invoice created, no payment yet
     *   Paying   — payer sent funds, awaiting blockchain confirmation
     *   Paid     — network confirmed, safe to credit
     *   Expired  — invoice expired without payment
     *   Failed   — payment failed
     */
    public static function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'paid'                                  => 'finished',
            'paying', 'confirming'                  => 'confirming',
            'waiting', 'new', 'created', 'pending'  => 'waiting',
            'expired', 'cancelled', 'canceled'      => 'expired',
            'failed', 'error'                       => 'failed',
            default                                 => 'waiting',
        };
    }
}
