<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDepositJob;
use App\Models\DepositLog;
use App\Models\PaymentGateway;
use App\Models\PaymentInvoice;
use App\Services\PaymentGateways\GatewayManager;
use App\Services\PaymentGateways\OxaPayGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles OxaPay payment webhook callbacks.
 *
 * OxaPay sends two callbacks per payment:
 *   1. status = "Paying"  — funds received, awaiting blockchain confirmation
 *   2. status = "Paid"    — network confirmed — we dispatch ProcessDepositJob here
 *
 * Security:
 *   - HMAC-SHA512 of raw body using merchant_api_key, compared with HMAC header
 *   - Idempotent: ProcessDepositJob checks credited_at inside a locked transaction
 *   - IP and raw payload stored for audit trail
 */
class OxaPayController extends Controller
{
    public function webhook(Request $request): Response
    {
        $rawBody  = $request->getContent();
        $payload  = json_decode($rawBody, true) ?? [];
        $clientIp = $request->ip();

        $signature = $request->header('HMAC', '')
            ?: $request->header('hmac', '');

        $trackId = (string) ($payload['track_id'] ?? '');
        $status  = (string) ($payload['status']   ?? '');
        $orderId = (string) ($payload['order_id'] ?? '');

        Log::info('[OxaPay Webhook] Received', [
            'track_id'  => $trackId,
            'status'    => $status,
            'order_id'  => $orderId,
            'ip'        => $clientIp,
            'sig_ok'    => '(pending verify)',
        ]);

        // ── 1. Load active OxaPay gateway ─────────────────────────────────────
        $gatewayModel = PaymentGateway::where('driver', 'oxapay')
            ->where('is_active', true)
            ->first();

        if (!$gatewayModel) {
            Log::error('[OxaPay Webhook] Gateway not found or inactive');
            return response('Gateway not configured', 503);
        }

        // ── 2. Verify HMAC-SHA512 signature ───────────────────────────────────
        $driver = GatewayManager::make($gatewayModel);

        if (!$driver->verifyCallback($rawBody, $signature)) {
            Log::warning('[OxaPay Webhook] Invalid signature', [
                'order_id' => $orderId,
                'ip'       => $clientIp,
            ]);

            // Try to log without invoice FK (order_id may not exist yet)
            if ($orderId) {
                $inv = PaymentInvoice::where('reference', $orderId)->first();
                if ($inv) {
                    DepositLog::record($inv->id, 'signature_invalid',
                        'HMAC verification failed',
                        ['ip' => $clientIp],
                    );
                }
            }

            return response('Invalid signature', 400);
        }

        // ── 3. Find our invoice ───────────────────────────────────────────────
        if (empty($orderId)) {
            Log::warning('[OxaPay Webhook] No order_id in payload');
            return response('ok');
        }

        $invoice = PaymentInvoice::where('reference', $orderId)
            ->where('gateway', 'oxapay')
            ->first();

        if (!$invoice) {
            Log::warning('[OxaPay Webhook] Invoice not found', ['order_id' => $orderId]);
            return response('ok');
        }

        // ── 4. Store webhook metadata ─────────────────────────────────────────
        $internalStatus = OxaPayGateway::mapStatus($status);

        // Extract blockchain hash and network from txs array
        $blockchainHash = null;
        $network        = null;
        $amountReceived = null;

        if (!empty($payload['txs']) && is_array($payload['txs'])) {
            $tx             = $payload['txs'][0] ?? [];
            $blockchainHash = $tx['txHash']  ?? $tx['tx_hash'] ?? null;
            $network        = $tx['network'] ?? null;
            $amountReceived = isset($tx['amount']) ? (float) $tx['amount'] : null;
        }

        $updateData = [
            'status'          => $internalStatus,
            'gateway_payload' => $payload,
            'ip_address'      => $clientIp,
        ];

        if ($blockchainHash)  $updateData['blockchain_hash']  = $blockchainHash;
        if ($network)         $updateData['network']          = $network;
        if ($amountReceived)  $updateData['amount_received']  = $amountReceived;
        if ($trackId)         $updateData['gateway_payment_id'] = $trackId;

        if (!empty($payload['currency'])) {
            $updateData['pay_currency'] = $payload['currency'];
        }

        // Set callback_received_at on first callback only
        if (!$invoice->callback_received_at) {
            $updateData['callback_received_at'] = now();
        }

        $invoice->update($updateData);

        DepositLog::record($invoice->id, 'webhook_received',
            "OxaPay callback: status={$status} → internal={$internalStatus}",
            [
                'track_id'        => $trackId,
                'status'          => $status,
                'blockchain_hash' => $blockchainHash,
                'network'         => $network,
                'ip'              => $clientIp,
            ],
        );

        Log::info('[OxaPay Webhook] Invoice updated', [
            'reference' => $invoice->reference,
            'status'    => $status,
            'internal'  => $internalStatus,
            'hash'      => $blockchainHash,
        ]);

        // ── 5. Dispatch credit job on "Paid" ──────────────────────────────────
        if ($internalStatus === PaymentInvoice::STATUS_FINISHED && !$invoice->isCredited()) {
            DepositLog::record($invoice->id, 'credit_queued',
                'Dispatching ProcessDepositJob',
                ['trigger' => 'oxapay_webhook'],
            );

            ProcessDepositJob::dispatch($invoice->id, 'oxapay_webhook');

            Log::info('[OxaPay Webhook] ProcessDepositJob dispatched', [
                'invoice' => $invoice->reference,
            ]);
        }

        return response('ok');
    }
}
