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
 * Handles OxaPay Invoice webhook callbacks for the oxapay_invoice driver.
 *
 * Identical HMAC-SHA512 verification as OxaPayController, but scoped to
 * invoices created via the coin-locked invoice flow (gateway = oxapay_invoice).
 */
class OxaPayInvoiceController extends Controller
{
    public function webhook(Request $request): Response
    {
        $rawBody  = $request->getContent();
        $payload  = json_decode($rawBody, true) ?? [];
        $data     = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $clientIp = $request->ip();

        $signature = $request->header('HMAC', '')
            ?: $request->header('hmac', '');

        $trackId = (string) ($data['track_id'] ?? $payload['track_id'] ?? '');
        $status  = (string) ($data['status']   ?? $payload['status']   ?? '');
        $orderId = (string) ($data['order_id'] ?? $data['orderId'] ?? $payload['order_id'] ?? '');

        Log::info('[OxaPayInvoice Webhook] Received', [
            'track_id' => $trackId,
            'status'   => $status,
            'order_id' => $orderId,
            'ip'       => $clientIp,
        ]);

        // ── 1. Load active oxapay_invoice gateway ─────────────────────────────
        $gatewayModel = PaymentGateway::where('driver', 'oxapay_invoice')
            ->where('is_active', true)
            ->first();

        if (!$gatewayModel) {
            Log::error('[OxaPayInvoice Webhook] Gateway not found or inactive');
            return response('Gateway not configured', 503);
        }

        // ── 2. Verify HMAC-SHA512 ─────────────────────────────────────────────
        $driver = GatewayManager::make($gatewayModel);

        if (!$driver->verifyCallback($rawBody, $signature)) {
            Log::warning('[OxaPayInvoice Webhook] Invalid signature', [
                'order_id' => $orderId,
                'ip'       => $clientIp,
            ]);

            if ($orderId) {
                $inv = PaymentInvoice::where('reference', $orderId)
                    ->where('gateway', 'oxapay_invoice')
                    ->first();
                if ($inv) {
                    DepositLog::record($inv->id, 'signature_invalid',
                        'HMAC verification failed',
                        ['ip' => $clientIp],
                    );
                }
            }

            return response('Invalid signature', 400);
        }

        // ── 3. Find invoice ───────────────────────────────────────────────────
        if (empty($orderId) && empty($trackId)) {
            Log::warning('[OxaPayInvoice Webhook] No order_id or track_id in payload');
            return response('ok');
        }

        $invoice = PaymentInvoice::where('gateway', 'oxapay_invoice')
            ->where(function ($query) use ($orderId, $trackId): void {
                if ($orderId !== '') {
                    $query->orWhere('reference', $orderId);
                }
                if ($trackId !== '') {
                    $query->orWhere('gateway_invoice_id', $trackId)
                        ->orWhere('gateway_payment_id', $trackId);
                }
            })
            ->first();

        if (!$invoice) {
            Log::warning('[OxaPayInvoice Webhook] Invoice not found', ['order_id' => $orderId]);
            return response('ok');
        }

        // ── 4. Update invoice metadata ────────────────────────────────────────
        $internalStatus = OxaPayGateway::mapStatus($status);

        $blockchainHash = null;
        $network        = null;
        $amountReceived = null;
        $confirmations  = null;

        if (!empty($data['txs']) && is_array($data['txs'])) {
            $tx             = $data['txs'][0] ?? [];
            $blockchainHash = $tx['txHash']       ?? $tx['tx_hash']      ?? null;
            $network        = $tx['network']       ?? null;
            $amountReceived = isset($tx['amount'])  ? (float) $tx['amount']  : null;
            $confirmations  = isset($tx['confirms']) ? (int) $tx['confirms'] : null;
        }

        $update = [
            'status'          => $internalStatus,
            'ip_address'      => $clientIp,
        ];

        if ($blockchainHash)         $update['blockchain_hash']   = $blockchainHash;
        if ($network)                $update['network']            = $network;
        if ($amountReceived)         $update['amount_received']    = $amountReceived;
        if ($confirmations !== null) $update['confirmations']      = $confirmations;
        if ($trackId)                $update['gateway_payment_id'] = $trackId;

        // White-label payload: pay_currency takes priority over currency
        $payCurrency = $data['pay_currency'] ?? $data['currency'] ?? null;
        if ($payCurrency) {
            $update['pay_currency'] = strtoupper($payCurrency);
        }

        // Preserve memo in gateway_payload (XRP destination tag / TON comment)
        $existingPayload = $invoice->gateway_payload ?? [];
        $newPayload      = array_merge($existingPayload, $data);
        if (!empty($data['memo'])) {
            $newPayload['memo'] = $data['memo'];
        }
        $update['gateway_payload'] = $newPayload;

        if (!$invoice->callback_received_at) {
            $update['callback_received_at'] = now();
        }

        $invoice->update($update);

        DepositLog::record($invoice->id, 'webhook_received',
            "OxaPayInvoice callback: status={$status} → internal={$internalStatus}",
            [
                'track_id'        => $trackId,
                'status'          => $status,
                'blockchain_hash' => $blockchainHash,
                'network'         => $network,
                'ip'              => $clientIp,
            ],
        );

        Log::info('[OxaPayInvoice Webhook] Invoice updated', [
            'reference' => $invoice->reference,
            'status'    => $status,
            'internal'  => $internalStatus,
            'hash'      => $blockchainHash,
        ]);

        // ── 5. Dispatch credit job on Paid ────────────────────────────────────
        if ($internalStatus === PaymentInvoice::STATUS_FINISHED && !$invoice->isCredited()) {
            DepositLog::record($invoice->id, 'credit_queued',
                'Dispatching ProcessDepositJob (sync with async fallback)',
                ['trigger' => 'oxapay_invoice_webhook'],
            );

            Log::info('[OxaPayInvoice Webhook] ProcessDepositJob dispatching', [
                'invoice' => $invoice->reference,
            ]);

            // Process synchronously so the credit completes even when no queue worker is running.
            // If sync execution throws (e.g. gateway re-verification timeout), fall back to the
            // async queue — the job will retry via backoff when a worker eventually picks it up.
            try {
                ProcessDepositJob::dispatchSync($invoice->id, 'oxapay_invoice_webhook');

                Log::info('[OxaPayInvoice Webhook] ProcessDepositJob completed synchronously', [
                    'invoice' => $invoice->reference,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[OxaPayInvoice Webhook] Sync dispatch failed — queuing async', [
                    'invoice' => $invoice->reference,
                    'error'   => $e->getMessage(),
                ]);

                ProcessDepositJob::dispatch($invoice->id, 'oxapay_invoice_webhook');
            }
        }

        return response('ok');
    }
}
