<?php

namespace App\Console\Commands;

use App\Models\NumberOrder;
use App\Models\SmsMessage;
use App\Services\NumberProviderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NumbersSync extends Command
{
    protected $signature   = 'numbers:sync {--limit=100}';
    protected $description = 'Poll active number orders for new SMS messages and status updates';

    public function __construct(private NumberProviderService $providerService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $orders = NumberOrder::with('provider')
            ->whereIn('status', ['PENDING', 'RECEIVED'])
            ->where('expires_at', '>', now())
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No active number orders to sync.');
            return 0;
        }

        $updated = 0;

        foreach ($orders as $order) {
            try {
                $driver   = $this->providerService->driver($order->provider);
                $response = $driver->checkOrder($order->activation_id);

                $newStatus = $this->providerService->normalizeStatus($response['status'] ?? $order->status);

                // Persist new SMS messages
                if (!empty($response['sms']) && is_array($response['sms'])) {
                    foreach ($response['sms'] as $sms) {
                        $text = $sms['text'] ?? '';
                        SmsMessage::firstOrCreate(
                            [
                                'number_order_id' => $order->id,
                                'received_at'     => \Carbon\Carbon::parse($sms['created_at'] ?? now()),
                            ],
                            [
                                'sender'       => $sms['sender'] ?? null,
                                'message'      => $text,
                                'code'         => $this->providerService->extractCode($text),
                                'raw_response' => $sms,
                            ]
                        );
                    }
                }

                $updateData = ['status' => $newStatus];

                $latestSms = $order->smsMessages()->orderByDesc('received_at')->first();
                if ($latestSms) {
                    $updateData['otp_code'] = $latestSms->code;
                    $updateData['sms_text'] = $latestSms->message;
                }

                if (in_array($newStatus, ['FINISHED', 'CANCELLED', 'BANNED', 'EXPIRED'])) {
                    $updateData['completed_at'] = now();
                }

                $order->update($updateData);
                $updated++;
            } catch (\Throwable $e) {
                Log::warning("numbers:sync failed for order {$order->id}: " . $e->getMessage());
            }
        }

        // Mark orders past their expiry as EXPIRED
        NumberOrder::whereIn('status', ['PENDING', 'RECEIVED'])
            ->where('expires_at', '<=', now())
            ->update(['status' => 'EXPIRED', 'completed_at' => now()]);

        $this->info("Synced {$updated}/{$orders->count()} active number orders.");
        return 0;
    }
}
