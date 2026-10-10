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
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $filterPid = $this->option('provider');
        $startedAt = now();

        $this->log('info', '[orders:update] START '.$startedAt->toISOString().($dryRun ? ' [DRY RUN]' : ''));

        $updated = 0;
        $errors = 0;

        // Oldest-synced first so every order gets regular attention; NULLs go first
        $orders = Order::whereIn('status', ['pending', 'processing'])
            ->whereNotNull('provider_order_id')
            ->with(['provider', 'service.provider', 'user.wallet'])
            ->when($filterPid, fn ($q) => $q->where('provider_id', $filterPid))
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

            if (! $provider instanceof Provider) {
                $provider = Provider::find($providerId);
            }

            if (! $provider || ! $provider->is_active) {
                $this->log('warning', "  Provider #{$providerId} inactive or not found — skipping {$providerOrders->count()} order(s).", [
                    'provider_id' => $providerId,
                    'order_count' => $providerOrders->count(),
                ]);

                continue;
            }

            $this->log('info', "  [{$provider->name}] checking {$providerOrders->count()} order(s)…");

            // Attempt batch status call
            $batch = [];
            $ids = $providerOrders->pluck('provider_order_id')->filter()->values()->all();

            $this->log('debug', "  [{$provider->name}] API request: status batch", [
                'provider' => $provider->name,
                'url' => $provider->base_url,
                'order_ids' => $ids,
            ]);

            try {
                $batch = $smm->checkMultipleOrdersLogged($provider, $ids);
                $this->log('debug', "  [{$provider->name}] API response: batch received", [
                    'provider' => $provider->name,
                    'result_count' => count($batch),
                    'ids_requested' => count($ids),
                ]);
            } catch (\Throwable $e) {
                $this->log('warning', "  [{$provider->name}] Batch call failed: {$e->getMessage()}", [
                    'provider' => $provider->name,
                    'error' => $e->getMessage(),
                ]);
            }

            foreach ($providerOrders as $order) {
                try {
                    $pid = (string) $order->provider_order_id;
                    $parsed = null;

                    if (! empty($batch) && array_key_exists($pid, $batch) && is_array($batch[$pid])) {
                        $parsed = $smm->parseStatusPayload($batch[$pid], $pid);

                        if (! $parsed['success']) {
                            $this->log('warning', "    Order #{$order->id}: invalid batch response — {$parsed['message']}", [
                                'order_id' => $order->id,
                                'provider_order' => $pid,
                            ]);
                            $errors++;

                            continue;
                        }
                    } else {
                        $this->log('debug', "    Order #{$order->id}: individual status request", [
                            'order_id' => $order->id,
                            'provider_order' => $pid,
                            'provider' => $provider->name,
                        ]);

                        $parsed = $smm->checkOrderStatus($provider, $pid);
                        if (! $parsed['success']) {
                            $this->log('warning', "    Order #{$order->id}: sync failed — {$parsed['message']}", [
                                'order_id' => $order->id,
                                'error' => $parsed['message'],
                            ]);
                            $errors++;

                            continue;
                        }
                    }

                    $rawStatus = $parsed['status'];
                    $startCount = $parsed['start_count_explicit'] ? $parsed['start_count'] : $order->start_count;
                    $remains = $parsed['remains_explicit'] ? $parsed['remains'] : $order->remains;
                    $resolution = $smm->resolveSyncedOrderStatus(
                        $order->status,
                        $rawStatus,
                        $remains,
                        (bool) $parsed['remains_explicit'],
                        (int) $order->quantity,
                    );
                    $newStatus = $resolution['status'];
                    $syncedAt = now();

                    $providerResponse = array_merge($order->provider_response ?? [], [
                        'last_raw_status' => $rawStatus,
                        'last_synced_at' => $syncedAt->toISOString(),
                        'start_count' => $startCount,
                        'remains' => $remains,
                        'remains_explicit' => (bool) $parsed['remains_explicit'],
                    ]);

                    if ($resolution['auto_completed']) {
                        $providerResponse['auto_completion_reason'] = $resolution['reason'];
                        $providerResponse['auto_completed_at'] = $syncedAt->toISOString();
                        $providerResponse['completion_evidence'] = [
                            'provider_order_id' => $pid,
                            'provider_status' => $rawStatus,
                            'remains' => 0,
                            'remains_explicit' => true,
                            'quantity' => (int) $order->quantity,
                        ];
                    }

                    $data = [
                        'last_synced_at' => $syncedAt,
                        'provider_response' => $providerResponse,
                    ];
                    $statusChanged = $newStatus !== $order->status;
                    $progressChanged = ($parsed['start_count_explicit'] && $startCount !== $order->start_count)
                        || ($parsed['remains_explicit'] && $remains !== $order->remains);

                    if ($statusChanged || $progressChanged) {
                        $data['status'] = $newStatus;
                        if ($parsed['start_count_explicit']) {
                            $data['start_count'] = $startCount;
                        }
                        if ($parsed['remains_explicit']) {
                            $data['remains'] = $remains;
                        }
                        if (in_array($newStatus, ['completed', 'canceled', 'partial', 'failed'], true)
                            && ! $order->processed_at) {
                            $data['processed_at'] = $syncedAt;
                        }

                        $arrow = $statusChanged ? "{$order->status} → {$newStatus}" : $order->status;
                        $this->log('info', "    Order #{$order->id}: {$arrow} (start:{$startCount} remains:{$remains})", [
                            'order_id' => $order->id,
                            'old_status' => $order->status,
                            'new_status' => $newStatus,
                            'raw_status' => $rawStatus,
                            'start_count' => $startCount,
                            'remains' => $remains,
                            'auto_completion_reason' => $resolution['reason'],
                        ]);
                        $updated++;
                    }

                    if (! $dryRun) {
                        $order->update($data);
                    }
                } catch (\Throwable $e) {
                    $this->log('error', "    Order #{$order->id}: exception — {$e->getMessage()}", [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errors++;
                }
            }
        }

        $elapsed = $startedAt->diffInSeconds(now());
        $label = $dryRun ? '[DRY RUN] ' : '';
        $this->log('info', "[orders:update] {$label}DONE — updated:{$updated} errors:{$errors} time:{$elapsed}s", [
            'updated' => $updated,
            'errors' => $errors,
            'elapsed' => $elapsed,
        ]);

        return self::SUCCESS;
    }
}
