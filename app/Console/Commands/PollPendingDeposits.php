<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDepositJob;
use App\Models\DepositLog;
use App\Models\PaymentGateway;
use App\Models\PaymentInvoice;
use App\Services\PaymentGateways\GatewayManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Backup safety net: polls gateway APIs for invoices that are still pending
 * but old enough that a webhook should have arrived. Fixes missed/failed webhooks.
 *
 * Runs every 5 minutes via scheduler (see routes/console.php).
 */
class PollPendingDeposits extends Command
{
    protected $signature = 'deposits:poll
        {--minutes=2   : Poll invoices older than this many minutes}
        {--limit=100   : Max invoices to process per run}
        {--dry-run     : Report what would be done without queuing jobs}';

    protected $description = 'Poll gateway APIs for pending deposits that may have been paid without webhook delivery';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $limit   = (int) $this->option('limit');
        $dryRun  = (bool) $this->option('dry-run');

        $cutoff = now()->subMinutes($minutes);

        // Find invoices still in a pending state that are old enough
        $invoices = PaymentInvoice::whereIn('status', PaymentInvoice::PENDING_STATUSES)
            ->where('created_at', '<=', $cutoff)
            ->whereNull('credited_at')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($invoices->isEmpty()) {
            $this->line('No pending invoices to poll.');
            return self::SUCCESS;
        }

        $this->line("Found {$invoices->count()} pending invoice(s) older than {$minutes} min.");

        $checked = $paid = $expired = $errors = 0;

        foreach ($invoices as $invoice) {
            $checked++;

            // Load the active gateway for this driver
            $gatewayModel = PaymentGateway::where('driver', $invoice->gateway)
                ->where('is_active', true)
                ->first();

            if (!$gatewayModel) {
                $this->warn("  [SKIP] {$invoice->reference} — gateway '{$invoice->gateway}' inactive");
                continue;
            }

            try {
                $driver = GatewayManager::make($gatewayModel);

                // Prefer gateway_invoice_id (track_id) for OxaPay; fallback to payment_id
                $paymentId = $invoice->gateway_invoice_id ?: $invoice->gateway_payment_id;

                if (!$paymentId) {
                    $this->warn("  [SKIP] {$invoice->reference} — no gateway payment ID to query");
                    continue;
                }

                $result = $driver->getPaymentStatus($paymentId);

                if (!$result['success']) {
                    $errors++;
                    $this->warn("  [ERR]  {$invoice->reference} — gateway query failed");
                    continue;
                }

                $remoteStatus = $result['status'];

                DepositLog::record($invoice->id, 'poll_triggered',
                    "Poll result: {$remoteStatus}",
                    ['payment_id' => $paymentId, 'raw' => $result['data'] ?? []],
                );

                if ($remoteStatus === 'finished') {
                    $paid++;
                    $this->line("  [PAID] {$invoice->reference} — crediting");

                    // Extract blockchain data from the API response if available
                    $apiData   = $result['data'] ?? [];
                    $txUpdate  = ['status' => 'finished'];
                    $txs       = $apiData['txs'] ?? [];
                    if (!empty($txs[0])) {
                        $tx = $txs[0];
                        if (!empty($tx['txHash'] ?? $tx['tx_hash'] ?? null)) {
                            $txUpdate['blockchain_hash'] = $tx['txHash'] ?? $tx['tx_hash'];
                        }
                        if (!empty($tx['amount'])) {
                            $txUpdate['amount_received'] = (float) $tx['amount'];
                        }
                    }
                    $invoice->update($txUpdate);

                    DepositLog::record($invoice->id, 'poll_paid',
                        'Gateway confirmed payment via background poll — crediting',
                    );

                    if (!$dryRun) {
                        // Try synchronous execution first (no queue worker required)
                        try {
                            ProcessDepositJob::dispatchSync($invoice->id, 'poll');
                        } catch (\Throwable $e) {
                            Log::warning('[deposits:poll] dispatchSync failed — queuing async', [
                                'invoice' => $invoice->reference,
                                'error'   => $e->getMessage(),
                            ]);
                            ProcessDepositJob::dispatch($invoice->id, 'poll');
                        }
                    }
                } elseif (in_array($remoteStatus, ['expired', 'failed'], true)) {
                    $expired++;
                    $this->line("  [DEAD] {$invoice->reference} — status: {$remoteStatus}");

                    if (!$dryRun) {
                        $invoice->update(['status' => $remoteStatus]);
                    }

                    DepositLog::record($invoice->id, 'poll_expired',
                        "Invoice {$remoteStatus} via poll",
                    );
                } else {
                    $this->line("  [WAIT] {$invoice->reference} — still {$remoteStatus}");
                }

            } catch (\Throwable $e) {
                $errors++;
                $this->error("  [ERR]  {$invoice->reference} — {$e->getMessage()}");
                Log::error('[deposits:poll] Exception', [
                    'invoice' => $invoice->reference,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->line("Done. checked={$checked} paid={$paid} expired={$expired} errors={$errors}");

        Log::info('[deposits:poll] Completed', compact('checked', 'paid', 'expired', 'errors'));

        return self::SUCCESS;
    }
}
