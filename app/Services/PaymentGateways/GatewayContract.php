<?php

namespace App\Services\PaymentGateways;

interface GatewayContract
{
    /**
     * Create a hosted invoice/checkout.
     * Returns ['success' => true, 'invoice_url' => string, 'gateway_invoice_id' => string]
     * or      ['success' => false, 'message' => string]
     */
    public function createInvoice(
        float  $amount,
        string $currency,
        string $reference,
        string $description,
        string $successUrl,
        string $cancelUrl,
        string $ipnUrl,
    ): array;

    /**
     * Verify an IPN/webhook callback signature.
     * $rawBody = raw POST bytes (before JSON-decode) — needed for accurate HMAC.
     */
    public function verifyCallback(string $rawBody, string $signature): bool;

    /**
     * Fetch current payment status from the gateway.
     * Returns ['success' => bool, 'status' => string, 'data' => array]
     */
    public function getPaymentStatus(string $paymentId): array;

    /** The driver identifier, e.g. "paymento". */
    public function getDriver(): string;

    /** Whether this gateway has valid credentials configured. */
    public function isConfigured(): bool;
}
