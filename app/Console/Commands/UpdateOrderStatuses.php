<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Provider;
use App\Services\SmmProviderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatuses extends Command
{
    protected $signature = 'orders:update
        {--provider= : Only sync orders for a specific provider ID}
        {--limit=200  : Max orders to process per run}
        {--dry-run    : Show what would change without saving}';

    protected $description = 'Poll SMM providers every minute and update pending/processing order statuses';

    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('orders')->{$level}($message, $context);
        if ($level === 'error') {
            $this->error($message);
        } elseif ($level === 'warning') {
            $this->warn($message);
        } else {
            $this->line($message);
        }
    }

    public function handle(SmmProviderService $smm): int
    {
        $limit     = (int) $this->option('limit');
        $dryRun    = (bool) $this->option('dry-run');
        $filterPid = $this->option('provider');
        $startedAt = now();

        $this->log('info', '[orders:update] START ' . $startedAt->toISOString() . ($dryRun ? ' [DRY RUN]' : ''));

        $updated = 0;
        $errors  = 0;

        // Oldest-synced first so every order gets regular attention; NULLs go first
        $orders = Order::whereIn('status', ['pending', 'processing'])
            ->whereNotNull('provider_order_id')
            ->with(['provider', 'service.provider', 'user.wallet'])
            ->when($filterPid, fn($q) => $q->where('provider_id', $filterPid))
            ->orderByRaw('last_synced_at IS NULL DESC')
            ->orderBy('last_synced_at')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->log('info', '[orders:update] No active orders to poll — done.');
            return self::SUCCESS;
        }

        $this->log('info', "[orders:update] Polling {$orders->count()} order(s)…");

        $byProvider = $orders->groupBy(function (Order $o) {
            return $o->provider_id ?? $o->service?->provider_id;
        });

        foreach ($byProvider as $providerId => $providerOrders) {
            $provider = $providerOrders->first()->provider
                ?? $providerOrders->first()->service?->provider;

            if (!$provider instanceof Provider) {
                $provider = Provider::find($providerId);
            }

            if (!$provider || !$provider->is_active) {
                $this->log('warning', "  Provider #{$providerId} inactive or not found — skipping {$providerOrders->count()} order(s).", [
                    'provider_id' => $providerId,
                    'order_count' => $providerOrders->count(),
                ]);
                continue;
            }

            $this->log('info', "  [{$provider->name}] checking {$providerOrders->count()} order(s)…");

            // Attempt batch status call
            $batch = [];
            $ids   = $providerOrders->pluck('provider_order_id')->filter()->values()->all();

            $this->log('debug', "  [{$provider->name}] API request: status batch", [
                'provider'  => $provider->name,
                'url'       => $provider->base_url,
                'order_ids' => $ids,
            ]);

            try {
                $batch = $smm->checkMultipleOrdersLogged($provider, $ids);
                $this->log('debug', "  [{$provider->name}] API response: batch received", [
                    'provider'      => $provider->name,
                    'result_count'  => count($batch),
                    'ids_requested' => count($ids),
                ]);
            } catch (\Throwable $e) {
                $this->log('warning', "  [{$provider->name}] Batch call failed: {$e->getMessage()}", [
                    'provider' => $provider->name,
                    'error'    => $e->getMessage(),
                ]);
            }

            foreach ($providerOrders as $order) {
                try {
                    $pid = $order->provider_order_id;

                    if (!empty($batch) && isset($batch[$pid])) {
                        // Use batch result
                        $raw        = $batch[$pid];
                        $rawStatus  = $raw['status'] ?? 'pending';
                        $startCount = (int) ($raw['start_count'] ?? $order->start_count ?? 0);
                        $remains    = (int) ($raw['remains']     ?? $order->remains     ?? 0);

                        $this->log('debug', "    Order #{$order->id} (batch): raw_status={$rawStatus}", [
                            'order_id'        => $order->id,
                            'provider_order'  => $pid,
                            'raw_status'      => $rawStatus,
                            'start_count'     => $startCount,
                            'remains'         => $remains,
                        ]);
                    } else {
                        // Fall back to individual call
                        $this->log('debug', "    Order #{$order->id}: individual status request", [
                            'order_id'       => $order->id,
                            'provider_order' => $pid,
                            'provider'       => $provider->name,
                        ]);

                        $res = $smm->checkOrderStatus($provider, $pid);

                        if (!$res['success']) {
                            $this->log('warning', "    Order #{$order->id}: sync failed — {$res['message']}", [
                                'order_id' => $order->id,
                                'error'    => $res['message'],
                            ]);
                            $errors++;

                            // Stamp last_synced_at even on failure so we don't hammer a bad order
                            if (!$dryRun) {
                                $order->update(['last_synced_at' => now()]);
                            }
                            continue;
                        }

                        $rawStatus  = $res['status'];
                        $startCount = (int) $res['start_count'];
                        $remains    = (int) $res['remains'];

                        $this->log('debug', "    Order #{$order->id} (individual): raw_status={$rawStatus}", [
                            'order_id'       => $order->id,
                            'provider_order' => $pid,
                            'raw_status'     => $rawStatus,
                            'start_count'    => $startCount,
                            'remains'        => $remains,
                            'raw_response'   => $res['raw'] ?? [],
                        ]);
                    }

                    $newStatus = $smm->normalizeStatus($rawStatus);

                    // Always stamp last_synced_at
                    $data = ['last_synced_at' => now()];

                    $statusChanged   = $newStatus !== $order->status;
                    $progressChanged = $startCount !== (int) ($order->start_count ?? 0)
                        || $remains !== (int) ($order->remains ?? 0);

                    if ($statusChanged || $progressChanged) {
                        $data['status']      = $newStatus;
                        $data['start_count'] = $startCount;
                        $data['remains']     = $remains;
                        $data['provider_response'] = array_merge($order->provider_response ?? [], [
                            'last_raw_status' => $rawStatus,
                            'last_synced_at'  => now()->toISOString(),
                            'start_count'     => $startCount,
                            'remains'         => $remains,
                        ]);

                        if (in_array($newStatus, ['completed', 'canceled', 'partial', 'failed'])
                            && !$order->processed_at) {
                            $data['processed_at'] = now();
                        }

                        $arrow = $statusChanged ? "{$order->status} → {$newStatus}" : $order->status;
                        $this->log('info', "    Order #{$order->id}: {$arrow} (start:{$startCount} remains:{$remains})", [
                            'order_id'    => $order->id,
                            'old_status'  => $order->status,
                            'new_status'  => $newStatus,
                            'raw_status'  => $rawStatus,
                            'start_count' => $startCount,
                            'remains'     => $remains,
                        ]);
                        $updated++;
                    } else {
                        $this->log('debug', "    Order #{$order->id}: no change (status={$order->status}, start:{$startCount} remains:{$remains})");
                    }

                    if (!$dryRun) {
                        $order->update($data);
                    }
                } catch (\Throwable $e) {
                    $this->log('error', "    Order #{$order->id}: exception — {$e->getMessage()}", [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                        'trace'    => $e->getTraceAsString(),
                    ]);
                    $errors++;
                }
            }
        }

        $elapsed = now()->diffInSeconds($startedAt);
        $label   = $dryRun ? '[DRY RUN] ' : '';
        $this->log('info', "[orders:update] {$label}DONE — updated:{$updated} errors:{$errors} time:{$elapsed}s", [
            'updated' => $updated,
            'errors'  => $errors,
            'elapsed' => $elapsed,
        ]);

        return self::SUCCESS;
    }
}
