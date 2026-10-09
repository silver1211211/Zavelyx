<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        if (!$this->option('force') && !$this->isDue()) {
            $this->line('Currency rates are not due yet.');
            return self::SUCCESS;
        }

        $lock = Cache::lock('currencies:sync', 120);

        if (!$lock->get()) {
            $this->line('Another currency-rate refresh is already running.');
            return self::SUCCESS;
        }

        $apiUrl  = Setting::get('currency.exchange_api_url', 'https://open.er-api.com/v6/latest/USD');
        $timeout = 15;

        try {
            Setting::set('currency.last_attempted_at', now()->toIso8601String());

            $response = Http::timeout($timeout)
                ->retry(2, 500, throw: false)
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

            if (($data['result'] ?? 'success') !== 'success') {
                $this->error('Exchange-rate provider returned an unsuccessful result.');
                return self::FAILURE;
            }

            if (isset($data['base_code']) && strtoupper((string) $data['base_code']) !== 'USD') {
                $this->error('Exchange-rate response is not based on USD.');
                return self::FAILURE;
            }

            if (!isset($data['rates']['USD']) || abs((float) $data['rates']['USD'] - 1.0) > 0.000001) {
                $this->error('Exchange-rate response contains an invalid USD base rate.');
                return self::FAILURE;
            }

            $rates = $data['rates'];
            $markupPercent = max(0, min(100, (float) Setting::get('currency.rate_markup_percent', '4')));
            $multiplier = 1 + ($markupPercent / 100);
            $currencies = Currency::query()
                ->where('is_active', true)
                ->where('auto_update', true)
                ->where('code', '!=', 'USD')
                ->get();
            $validatedRates = [];

            foreach ($currencies as $currency) {
                $code = strtoupper($currency->code);

                if (!isset($rates[$code]) || !is_numeric($rates[$code])) {
                    $this->error("Exchange-rate response is missing {$code}; no rates were changed.");
                    return self::FAILURE;
                }

                $sourceRate = (float) $rates[$code];
                if (!is_finite($sourceRate) || $sourceRate <= 0 || $sourceRate > 1_000_000_000) {
                    $this->error("Exchange-rate response contains an invalid {$code} rate; no rates were changed.");
                    return self::FAILURE;
                }

                $validatedRates[$currency->id] = [
                    'source_exchange_rate' => round($sourceRate, 10),
                    'exchange_rate' => round($sourceRate * $multiplier, 6),
                ];
            }

            DB::transaction(function () use ($currencies, $validatedRates): void {
                foreach ($currencies as $currency) {
                    $currency->update($validatedRates[$currency->id]);
                }

                Currency::where('code', 'USD')->update([
                    'exchange_rate' => 1,
                    'source_exchange_rate' => 1,
                    'auto_update' => false,
                ]);
            });

            $syncedAt = now()->toIso8601String();
            Setting::set('currency.last_synced_at', $syncedAt);

            $updated = count($validatedRates);
            $this->info("currencies:sync done — updated {$updated} automatic currencies with {$markupPercent}% markup.");
            Log::info('currencies:sync completed', [
                'updated' => $updated,
                'markup_percent' => $markupPercent,
                'synced_at' => $syncedAt,
            ]);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('currencies:sync exception: ' . $e->getMessage());
            $this->error('Exception: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    private function isDue(): bool
    {
        $interval = max(5, min(1440, (int) Setting::get('currency.exchange_refresh_interval', '1440')));
        $lastAttemptedAt = Setting::get('currency.last_attempted_at');

        if (!$lastAttemptedAt) {
            return true;
        }

        try {
            return Carbon::parse($lastAttemptedAt)->addMinutes($interval)->isPast();
        } catch (\Throwable) {
            return true;
        }
    }
}
