<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\SmmProviderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public readonly Order $order)
    {
        $this->onQueue('orders');
    }

    public function handle(SmmProviderService $smm): void
    {
        $order = $this->order->fresh(['service.provider']);

        if (!$order || $order->status !== 'pending') {
            return;
        }

        $result = $smm->placeOrder($order);

        if ($result['success']) {
            $payload       = $order->payload ?? [];
            $quantity      = (int) ($payload['quantity'] ?? $order->quantity ?? 0);
            $costPer1k     = (float) ($payload['cost_per_1k'] ?? 0);
            $providerPrice = round(($quantity / 1000) * $costPer1k, 8);
            $userPrice     = (float) $order->amount;
            $profit        = round($userPrice - $providerPrice, 8);

            $order->update([
                'provider_order_id' => $result['provider_order_id'],
                'status'            => 'processing',
                'processed_at'      => now(),
                'provider_response' => array_merge($order->provider_response ?? [], [
                    'provider_price'     => $providerPrice,
                    'user_price'         => $userPrice,
                    'profit'             => $profit,
                    'placed_at'          => now()->toISOString(),
                ]),
            ]);

            Log::info('Order submitted to provider', [
                'order_id'          => $order->id,
                'provider_order_id' => $result['provider_order_id'],
                'profit'            => $profit,
            ]);
        } else {
            Log::warning('Order placement failed', [
                'order_id' => $order->id,
                'reason'   => $result['message'],
            ]);

            // Keep status as pending so cron can retry or admin can see it
            $order->update([
                'provider_response' => array_merge($order->provider_response ?? [], [
                    'placement_error' => $result['message'],
                    'attempted_at'    => now()->toISOString(),
                ]),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessOrderJob permanently failed', [
            'order_id' => $this->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }
}
