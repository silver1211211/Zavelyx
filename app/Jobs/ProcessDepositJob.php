<?php

namespace App\Jobs;

use App\Models\DepositLog;
use App\Models\Notification;
use App\Models\PaymentGateway;
use App\Models\PaymentInvoice;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\PaymentGateways\GatewayManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles the actual wallet-credit step for a completed deposit.
 *
 * Dispatched by:
 *  - OxaPayController  on "Paid" status
 *  - PaymentoController on OrderStatus 7
 *  - Admin retry action
 *  - PollPendingDepositsCommand when it detects a paid invoice
 *
 * Idempotent: will silently skip if invoice is already credited.
 * Uses pessimistic locking (SELECT … FOR UPDATE) on the wallet row.
 */
class ProcessDepositJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 5;
    public int $timeout = 30;

    public function __construct(
        public readonly int    $invoiceId,
        public readonly string $triggerSource = 'webhook',
    ) {
        $this->onQueue('deposits');
    }

    /** Exponential back-off: 30s → 60s → 2m → 5m → 10m */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
    }

    public function handle(): void
    {
        $invoice = PaymentInvoice::find($this->invoiceId);

        if (!$invoice) {
            return;
        }

        // ── Idempotency guards ────────────────────────────────────────────────
        if ($invoice->isCredited()) {
            DepositLog::record($invoice->id, 'credit_skipped', 'Already credited — idempotent skip', [
                'credited_at' => $invoice->credited_at?->toISOString(),
            ]);
            return;
        }

        if ($invoice->status !== PaymentInvoice::STATUS_FINISHED) {
            DepositLog::record($invoice->id, 'credit_skipped', "Status is [{$invoice->status}], not finished");
            return;
        }

        DepositLog::record($invoice->id, 'credit_attempted', "Attempt #{$this->attempts()}", [
            'trigger' => $this->triggerSource,
        ]);

        $invoice->increment('retry_count');

        // ── Verify payment with gateway API (double-check webhook) ────────────
        $this->verifyWithGateway($invoice);

        // ── Look up gateway fee ───────────────────────────────────────────────
        $gatewayModel = PaymentGateway::where('driver', $invoice->gateway)->first();
        $feePercent   = $gatewayModel ? (float) $gatewayModel->fee_percent : 0.0;
        $grossAmount  = (float) $invoice->price_amount;
        $netAmount    = $feePercent > 0
            ? round($grossAmount * (1 - $feePercent / 100), 8)
            : $grossAmount;

        DB::transaction(function () use ($invoice, $netAmount, $grossAmount, $feePercent) {
            // Re-check inside transaction (prevents race between concurrent jobs)
            $fresh = PaymentInvoice::lockForUpdate()->find($invoice->id);

            if (!$fresh || $fresh->isCredited()) {
                return;
            }

            $wallet = Wallet::where('user_id', $fresh->user_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet || !$wallet->is_active) {
                throw new \RuntimeException(
                    "Wallet inactive or missing for user {$fresh->user_id}"
                );
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter  = round($balanceBefore + $netAmount, 8);

            $wallet->forceFill([
                'balance'        => $balanceAfter,
                'ledger_balance' => $balanceAfter,
            ])->save();

            Transaction::create([
                'reference'      => Str::uuid()->toString(),
                'user_id'        => $fresh->user_id,
                'wallet_id'      => $wallet->id,
                'type'           => 'credit',
                'amount'         => $netAmount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'status'         => 'completed',
                'description'    => 'Crypto deposit via ' . ucfirst($fresh->gateway),
                'metadata'       => [
                    'gateway'          => $fresh->gateway,
                    'invoice_ref'      => $fresh->reference,
                    'blockchain_hash'  => $fresh->blockchain_hash,
                    'gross_amount'     => $grossAmount,
                    'fee_percent'      => $feePercent,
                    'net_amount'       => $netAmount,
                    'trigger_source'   => $this->triggerSource,
                ],
            ]);

            $fresh->update([
                'credited_at'    => now(),
                'processed_at'   => now(),
                'failure_reason' => null,
            ]);
        });

        DepositLog::record($invoice->id, 'credit_succeeded',
            "Credited \${$netAmount} (gross: \${$grossAmount}, fee: {$feePercent}%)",
            ['net' => $netAmount, 'gross' => $grossAmount, 'fee' => $feePercent],
        );

        $this->notifyUser($invoice->user_id, $invoice->reference, $netAmount);

        Log::info('[ProcessDepositJob] Credit succeeded', [
            'invoice' => $invoice->reference,
            'user_id' => $invoice->user_id,
            'net'     => $netAmount,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[ProcessDepositJob] Permanently failed', [
            'invoice_id' => $this->invoiceId,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);

        $invoice = PaymentInvoice::find($this->invoiceId);

        if (!$invoice) {
            return;
        }

        $invoice->update(['failure_reason' => $e->getMessage()]);

        DepositLog::record($invoice->id, 'credit_failed_permanent',
            "Permanently failed after {$this->tries} attempts: " . $e->getMessage(),
            ['exception' => $e->getMessage()],
        );

        Notification::create([
            'user_id'      => $invoice->user_id,
            'type'         => 'deposit_failed',
            'category'     => 'finance',
            'priority'     => 'error',
            'title'        => 'Deposit Processing Issue',
            'message'      => 'Your deposit could not be processed automatically. '
                . 'Please contact support with reference: ' . $invoice->reference,
            'data'         => ['invoice_ref' => $invoice->reference],
            'action_url'   => '/tickets',
            'action_label' => 'Contact Support',
        ]);
    }

    /**
     * Verify the payment with the gateway API before crediting.
     * This is a double-check on top of the webhook to prevent fraud.
     * Throws RuntimeException to trigger job retry on transient errors.
     */
    private function verifyWithGateway(PaymentInvoice $invoice): void
    {
        $paymentId = $invoice->gateway_payment_id ?: $invoice->gateway_invoice_id;

        if (!$paymentId) {
            DepositLog::record($invoice->id, 'gateway_verify_skipped',
                'No payment ID available — skipping API verification (webhook-only mode)',
            );
            return;
        }

        $gatewayModel = PaymentGateway::where('driver', $invoice->gateway)
            ->where('is_active', true)
            ->first();

        if (!$gatewayModel) {
            DepositLog::record($invoice->id, 'gateway_verify_skipped',
                "Gateway [{$invoice->gateway}] not found — proceeding without API verification",
            );
            return;
        }

        try {
            $driver = GatewayManager::make($gatewayModel);
            $result = $driver->getPaymentStatus($paymentId);

            $verifiedStatus = $result['status'] ?? 'unknown';
            $apiData        = $result['data']   ?? [];

            DepositLog::record($invoice->id, 'gateway_verified',
                "API verification: status={$verifiedStatus} success=" . ($result['success'] ? 'yes' : 'no'),
                ['payment_id' => $paymentId, 'status' => $verifiedStatus, 'api_success' => $result['success']],
            );

            Log::info('[ProcessDepositJob] Gateway verification', [
                'invoice'    => $invoice->reference,
                'payment_id' => $paymentId,
                'api_status' => $verifiedStatus,
                'success'    => $result['success'],
            ]);

            if (!$result['success']) {
                // Transient API error — retry the job
                throw new \RuntimeException(
                    "OxaPay API verification failed (network/timeout) — will retry"
                );
            }

            if ($verifiedStatus === 'finished') {
                return; // All good — proceed to credit
            }

            // Terminal state from gateway API — stop retrying
            if (in_array($verifiedStatus, ['expired', 'failed', 'cancelled', 'refunded'], true)) {
                $invoice->update(['status' => $verifiedStatus, 'failure_reason' => "Gateway API reports: {$verifiedStatus}"]);
                DepositLog::record($invoice->id, 'gateway_verify_terminal',
                    "Gateway API says terminal: {$verifiedStatus} — aborting credit",
                );
                $this->delete(); // Remove from queue permanently
                return;
            }

            // Still pending/confirming — retry after backoff
            throw new \RuntimeException(
                "Gateway API reports status [{$verifiedStatus}] — not ready to credit yet"
            );

        } catch (\RuntimeException $e) {
            throw $e; // Let the queue retry mechanism handle it
        } catch (\Throwable $e) {
            // Unexpected error during verification — log but don't block crediting
            Log::error('[ProcessDepositJob] Verification error — proceeding without verification', [
                'invoice' => $invoice->reference,
                'error'   => $e->getMessage(),
            ]);
            DepositLog::record($invoice->id, 'gateway_verify_error',
                'Verification exception — proceeding: ' . $e->getMessage(),
            );
        }
    }

    private function notifyUser(int $userId, string $ref, float $amount): void
    {
        Notification::create([
            'user_id'      => $userId,
            'type'         => 'deposit_success',
            'category'     => 'finance',
            'priority'     => 'success',
            'title'        => 'Deposit Confirmed',
            'message'      => '$' . number_format($amount, 2) . ' has been credited to your wallet.',
            'data'         => ['invoice_ref' => $ref, 'amount' => $amount],
            'action_url'   => '/deposit',
            'action_label' => 'View Deposits',
        ]);
    }
}
