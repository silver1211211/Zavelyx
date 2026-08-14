<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDepositJob;
use App\Models\DepositLog;
use App\Models\PaymentGateway;
use App\Models\PaymentInvoice;
use App\Services\PaymentGateways\GatewayManager;
use App\Services\PaymentGateways\PaymentoGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles Paymento IPN callbacks.
 *
 * Paymento sends HMAC-SHA256 of raw body → X-HMAC-SHA256-SIGNATURE header.
 * OrderStatus 7 = paid/confirmed → dispatch ProcessDepositJob.
 */
class PaymentoController extends Controller
{
    public function ipn(Request $request): JsonResponse
    {
        $rawBody  = $request->getContent();
        $payload  = json_decode($rawBody, true) ?? [];
        $clientIp = $request->ip();

        $signature = $request->header('X-HMAC-SHA256-SIGNATURE', '')
            ?: $request->header('x-hmac-sha256-signature', '')
            ?: $request->header('X-Paymento-Signature', '');

        $orderId     = (string) ($payload['OrderId']     ?? $payload['orderId']     ?? '');
        $orderStatus = (string) ($payload['OrderStatus'] ?? $payload['status']      ?? '');
        $paymentId   = (string) ($payload['PaymentId']   ?? $payload['paymentId']   ?? '');
        $token       = (string) ($payload['Token']       ?? $payload['token']       ?? '');

        Log::info('[Paymento IPN] Received', [
            'order_id'     => $orderId,
            'order_status' => $orderStatus,
            'payment_id'   => $paymentId,
            'ip'           => $clientIp,
        ]);

        // ── 1. Load active Paymento gateway ──────────────────────────────────
        $gatewayModel = PaymentGateway::where('driver', 'paymento')
            ->where('is_active', true)
            ->first();

        if (!$gatewayModel) {
            Log::error('[Paymento IPN] Gateway not found or inactive');
            return response()->json(['error' => 'Gateway not configured'], 503);
        }

        // ── 2. Verify HMAC signature ──────────────────────────────────────────
        $driver = GatewayManager::make($gatewayModel);

        if (!$driver->verifyCallback($rawBody, $signature)) {
            Log::warning('[Paymento IPN] Invalid signature', [
                'order_id' => $orderId,
                'ip'       => $clientIp,
            ]);

            if ($orderId) {
                $inv = PaymentInvoice::where('reference', $orderId)->first();
                if ($inv) {
                    DepositLog::record($inv->id, 'signature_invalid',
                        'HMAC verification failed',
                        ['ip' => $clientIp],
                    );
                }
            }

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // ── 3. Find our invoice ───────────────────────────────────────────────
        $invoice = PaymentInvoice::where('reference', $orderId)->first();

        if (!$invoice) {
            Log::warning('[Paymento IPN] Invoice not found', ['order_id' => $orderId]);
            return response()->json(['status' => 'order_not_found'], 200);
        }

        // ── 4. Map status → internal ──────────────────────────────────────────
        $internalStatus = PaymentoGateway::mapStatus($orderStatus);

        // ── 5. Store webhook metadata ─────────────────────────────────────────
        $updateData = [
            'status'          => $internalStatus,
            'gateway_payload' => $payload,
            'ip_address'      => $clientIp,
        ];

        if ($paymentId) {
            $updateData['gateway_payment_id'] = $paymentId;
        }

        // Set callback_received_at on first callback only
        if (!$invoice->callback_received_at) {
            $updateData['callback_received_at'] = now();
        }

        $invoice->update($updateData);

        DepositLog::record($invoice->id, 'webhook_received',
            "Paymento IPN: OrderStatus={$orderStatus} → internal={$internalStatus}",
            [
                'order_status' => $orderStatus,
                'payment_id'   => $paymentId,
                'ip'           => $clientIp,
            ],
        );

        Log::info('[Paymento IPN] Invoice updated', [
            'invoice_ref'     => $invoice->reference,
            'order_status'    => $orderStatus,
            'internal_status' => $internalStatus,
        ]);

        // ── 6. Dispatch credit job on "Paid" (OrderStatus 7) ─────────────────
        if ($internalStatus === PaymentInvoice::STATUS_FINISHED && !$invoice->isCredited()) {
            DepositLog::record($invoice->id, 'credit_queued',
                'Dispatching ProcessDepositJob',
                ['trigger' => 'paymento_ipn'],
            );

            ProcessDepositJob::dispatch($invoice->id, 'paymento_ipn');

            Log::info('[Paymento IPN] ProcessDepositJob dispatched', [
                'invoice' => $invoice->reference,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
