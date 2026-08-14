<?php

use App\Http\Controllers\Api\OxaPayController;
use App\Http\Controllers\Api\OxaPayInvoiceController;
use App\Http\Controllers\Api\PaymentoController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use App\Jobs\ProcessDepositJob;
use App\Models\DepositLog;
use App\Models\PaymentGateway;
use App\Models\PaymentInvoice;
use App\Services\PaymentGateways\GatewayManager;
use App\Services\PaymentGateways\OxaPayInvoiceGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// ── Payment webhooks (public — no auth, signature-verified internally) ───────
Route::post('/payments/paymento/ipn',           [PaymentoController::class,        'ipn'])
    ->name('paymento.ipn');

Route::post('/payments/oxapay/webhook',         [OxaPayController::class,          'webhook'])
    ->name('oxapay.webhook');

Route::post('/payments/oxapay-invoice/webhook', [OxaPayInvoiceController::class,   'webhook'])
    ->name('oxapay_invoice.webhook');

// ── Authenticated API endpoints ────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/wallet',       [WalletController::class,     'show']);
    Route::get('/transactions', [TransactionController::class,'index']);
    Route::get('/services',     [ServiceController::class,    'index']);

    // OxaPay Invoice — accepted coin list (cached 1h in the gateway)
    Route::get('/deposit/invoice/coins', function () {
        $gateway = PaymentGateway::where('driver', 'oxapay_invoice')
            ->where('is_active', true)
            ->first();

        if (!$gateway || !$gateway->isConfigured()) {
            return response()->json(['coins' => [], 'source' => 'unavailable']);
        }

        /** @var OxaPayInvoiceGateway $driver */
        $driver = GatewayManager::make($gateway);
        $coins  = $driver->getAcceptedCoins();

        return response()->json(['coins' => $coins, 'source' => 'oxapay']);
    })->name('deposit.invoice.coins');

    // Deposit status polling — used by frontend to check payment progress.
    // Performs a live OxaPay inquiry on every poll so the UI updates the instant
    // OxaPay confirms payment, even when webhooks are unreachable (local dev / firewall).
    Route::get('/deposits/{reference}/status', function (string $reference, Request $request) {
        $invoice = PaymentInvoice::where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Deposit not found.'], 404);
        }

        // ── CASE 1: Pending invoice → live OxaPay check ───────────────────────
        // This path is the primary detection mechanism when webhooks can't reach the server
        // (local dev, firewall, etc.). Runs on every 5-second frontend poll.
        if ($invoice->isPending() && !$invoice->isCredited() && $invoice->gateway_invoice_id) {
            $gatewayModel = PaymentGateway::where('driver', $invoice->gateway)
                ->where('is_active', true)
                ->first();

            if ($gatewayModel) {
                try {
                    $driver = GatewayManager::make($gatewayModel);
                    $result = $driver->getPaymentStatus($invoice->gateway_invoice_id);

                    if ($result['success']) {
                        $newStatus = $result['status'];
                        $apiData   = $result['data'] ?? [];

                        if ($newStatus === 'finished') {
                            // Extract blockchain metadata from the inquiry response
                            $update = ['status' => 'finished'];
                            $txs    = $apiData['txs'] ?? [];
                            if (!empty($txs[0])) {
                                $tx = $txs[0];
                                if (!empty($tx['txHash'] ?? $tx['tx_hash'] ?? null)) {
                                    $update['blockchain_hash'] = $tx['txHash'] ?? $tx['tx_hash'];
                                }
                                if (!empty($tx['amount'])) {
                                    $update['amount_received'] = (float) $tx['amount'];
                                }
                                if (!empty($tx['network'])) {
                                    $update['network'] = $tx['network'];
                                }
                            }

                            $invoice->update($update);

                            DepositLog::record($invoice->id, 'poll_paid_frontend',
                                'Frontend poll detected Paid status via OxaPay inquiry',
                                ['track_id' => $invoice->gateway_invoice_id, 'api_data' => $apiData],
                            );

                            Log::info('[poll/status] Payment detected', [
                                'invoice'  => $invoice->reference,
                                'track_id' => $invoice->gateway_invoice_id,
                            ]);

                            // Credit synchronously — no queue worker required.
                            try {
                                ProcessDepositJob::dispatchSync($invoice->id, 'frontend_poll');
                            } catch (\Throwable $e) {
                                // Sync failed (e.g. OxaPay re-verification timed out).
                                // Dispatch to async queue as backup; CASE 2 (below) will
                                // retry sync on the next poll cycle 5 s from now.
                                Log::warning('[poll/status] dispatchSync failed — queuing async fallback', [
                                    'invoice' => $invoice->reference,
                                    'error'   => $e->getMessage(),
                                ]);
                                ProcessDepositJob::dispatch($invoice->id, 'frontend_poll');
                            }

                            $invoice->refresh();

                        } elseif ($newStatus === 'confirming' && $invoice->status === 'waiting') {
                            $invoice->update(['status' => 'confirming']);
                            $invoice->refresh();

                        } elseif (in_array($newStatus, ['expired', 'failed'], true)
                            && $newStatus !== $invoice->status
                        ) {
                            $invoice->update(['status' => $newStatus]);
                            $invoice->refresh();
                        }
                    }
                } catch (\Throwable $e) {
                    // Non-fatal: return whatever we have in the DB; frontend retries in 5 s
                    Log::warning('[poll/status] Live OxaPay check exception', [
                        'invoice' => $invoice->reference,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        }

        // ── CASE 2: Finished but not yet credited ─────────────────────────────
        // Happens when: OxaPay says Paid, we set status=finished, but the sync
        // dispatchSync above threw (OxaPay re-verify timeout) and the async queue
        // worker hasn't picked it up yet.  Retry sync on every poll until it lands.
        if ($invoice->isFinished() && !$invoice->isCredited()) {
            try {
                ProcessDepositJob::dispatchSync($invoice->id, 'frontend_poll_retry');
                $invoice->refresh();
            } catch (\Throwable $e) {
                Log::warning('[poll/status] Retry dispatchSync failed', [
                    'invoice' => $invoice->reference,
                    'error'   => $e->getMessage(),
                ]);
                // Keep returning the current DB state; next poll will try again
            }
        }

        return response()->json([
            'status'          => $invoice->status,
            'status_label'    => PaymentInvoice::statusLabel($invoice->status),
            'is_credited'     => $invoice->isCredited(),
            'is_finished'     => $invoice->isFinished(),
            'is_terminal'     => $invoice->isTerminal(),
            'price_amount'    => (float) $invoice->price_amount,
            'price_currency'  => strtoupper($invoice->price_currency ?? 'USD'),
            'pay_currency'    => $invoice->pay_currency ? strtoupper($invoice->pay_currency) : null,
            'memo'            => $invoice->gateway_payload['memo'] ?? null,
            'blockchain_hash' => $invoice->blockchain_hash,
            'network'         => $invoice->network,
            'credited_at'     => $invoice->credited_at?->toISOString(),
            'amount_received' => $invoice->amount_received ? (float) $invoice->amount_received : null,
        ]);
    })->where('reference', '[0-9a-f-]{36}');
});
