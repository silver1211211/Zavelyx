<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessRefunds extends Command
{
    protected $signature = 'orders:refund
        {--limit=200 : Max orders to process per run}
        {--dry-run : Preview refunds without executing}';

    protected $description = 'Issue automatic refunds for canceled, partial, and failed orders';

    public function handle(): int
    {
        $limit  = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $processed = 0;
        $skipped   = 0;
        $errors    = 0;

        // Find orders that need a refund and haven't been refunded yet
        $orders = Order::whereIn('status', ['canceled', 'partial', 'failed'])
            ->whereNull('refund_status')
            ->with('user.wallet')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No pending refunds.');
            return self::SUCCESS;
        }

        $this->info("Processing {$orders->count()} refund(s)…");

        foreach ($orders as $order) {
            try {
                $wallet = $order->user?->wallet;

                if (!$wallet) {
                    $this->warn("  Order #{$order->id}: user has no wallet — skipping.");
                    // Mark so we don't keep checking
                    if (!$dryRun) {
                        $order->update(['refund_status' => 'skipped']);
                    }
                    $skipped++;
                    continue;
                }

                // Guard: if a credit transaction already exists for this order, don't double-refund
                $alreadyRefunded = Transaction::where('order_id', $order->id)
                    ->where('type', 'credit')
                    ->exists();

                if ($alreadyRefunded) {
                    $this->warn("  Order #{$order->id}: credit transaction already exists — marking complete.");
                    if (!$dryRun) {
                        $order->update(['refund_status' => 'completed']);
                    }
                    $skipped++;
                    continue;
                }

                $refundAmount = $this->calculateRefund($order);

                if ($refundAmount <= 0) {
                    // Nothing to refund (e.g. partial with 0 remains) — mark complete
                    $this->line("  Order #{$order->id} ({$order->status}): nothing to refund.");
                    if (!$dryRun) {
                        $order->update(['refund_status' => 'completed', 'refund_amount' => 0]);
                    }
                    $skipped++;
                    continue;
                }

                $this->line("  Order #{$order->id} ({$order->status}): refunding \${$refundAmount}");

                if (!$dryRun) {
                    DB::transaction(function () use ($order, $wallet, $refundAmount) {
                        $balanceBefore = (float) $wallet->balance;

                        $wallet->increment('balance', $refundAmount);
                        $wallet->refresh();

                        Transaction::create([
                            'reference'      => Str::uuid(),
                            'user_id'        => $order->user_id,
                            'wallet_id'      => $wallet->id,
                            'order_id'       => $order->id,
                            'type'           => 'credit',
                            'amount'         => $refundAmount,
                            'balance_before' => $balanceBefore,
                            'balance_after'  => (float) $wallet->balance,
                            'status'         => 'completed',
                            'description'    => "Auto-refund: Order #{$order->id} ({$order->status})",
                        ]);

                        $order->update([
                            'refund_status' => 'completed',
                            'refund_amount' => $refundAmount,
                            'refunded_at'   => now(),
                        ]);
                    });

                    Log::channel('orders')->info('Refund action: auto-refund processed', [
                        'order_id'      => $order->id,
                        'status'        => $order->status,
                        'refund_amount' => $refundAmount,
                        'user_id'       => $order->user_id,
                        'balance_after' => (float) $wallet->fresh()->balance,
                    ]);
                }

                $processed++;
            } catch (\Throwable $e) {
                Log::channel('orders')->error("Refund action: FAILED for order #{$order->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("  Order #{$order->id}: FAILED — {$e->getMessage()}");
                $errors++;
            }
        }

        $label = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$label}orders:refund complete — processed:{$processed}, skipped:{$skipped}, errors:{$errors}");

        return self::SUCCESS;
    }

    private function calculateRefund(Order $order): float
    {
        $status   = $order->status;
        $amount   = (float) $order->amount;
        $quantity = (int) $order->quantity;
        $remains  = (int) ($order->remains ?? 0);

        // Full refund for canceled or failed orders
        if ($status === 'canceled' || $status === 'failed') {
            return $amount;
        }

        // Partial refund: refund only the undelivered portion
        if ($status === 'partial') {
            if ($quantity <= 0 || $remains <= 0) {
                return 0.0;
            }
            $unitPrice = $amount / $quantity;
            return round($unitPrice * $remains, 8);
        }

        return 0.0;
    }
}
