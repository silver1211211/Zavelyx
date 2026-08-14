<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncCurrencyRates extends Command
{
    protected $signature   = 'currencies:sync {--force : Ignore live-rates-enabled setting}';
    protected $description = 'Fetch live exchange rates from open.er-api.com and update currency table';

    public function handle(): int
    {
        $enabled = Setting::get('currency.live_rates_enabled', '0');

        if (!$enabled && !$this->option('force')) {
            $this->line('Live rate fetching is disabled. Use --force to override.');
            return 0;
        }

        $apiUrl  = Setting::get('currency.exchange_api_url', 'https://open.er-api.com/v6/latest/USD');
        $timeout = 15;

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($apiUrl);

            if ($response->failed()) {
                Log::error("currencies:sync HTTP error [{$response->status()}]: " . $response->body());
                $this->error("API request failed: HTTP {$response->status()}");
                return 1;
            }

            $data = $response->json();

            if (empty($data['rates']) || !is_array($data['rates'])) {
                Log::error('currencies:sync: unexpected response format', ['body' => substr($response->body(), 0, 500)]);
                $this->error('Unexpected API response format — no "rates" key found.');
                return 1;
            }

            $rates    = $data['rates'];
            $updated  = 0;
            $skipped  = 0;

            // Never auto-update USD (always 1.0 by definition)
            $currencies = Currency::whereNotIn('code', ['USD'])->get();

            foreach ($currencies as $currency) {
                $code = strtoupper($currency->code);

                if (!isset($rates[$code])) {
                    $skipped++;
                    continue;
                }

                $newRate = (float) $rates[$code];

                if ($newRate <= 0) {
                    $skipped++;
                    continue;
                }

                $currency->update(['exchange_rate' => $newRate]);
                $updated++;
            }

            Setting::set('currency.last_synced_at', now()->toIso8601String());

            $this->info("currencies:sync done — updated {$updated}, skipped {$skipped}.");
            Log::info("currencies:sync completed: updated={$updated} skipped={$skipped}");

            return 0;

        } catch (\Throwable $e) {
            Log::error('currencies:sync exception: ' . $e->getMessage());
            $this->error('Exception: ' . $e->getMessage());
            return 1;
        }
    }
}
