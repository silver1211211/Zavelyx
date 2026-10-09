<?php

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Setting::set('currency.live_rates_enabled', '1');
    Setting::set('currency.exchange_api_url', 'https://open.er-api.com/v6/latest/USD');
    Setting::set('currency.exchange_refresh_interval', '1440');
    Setting::set('currency.rate_markup_percent', '4');

    Currency::where('code', '!=', 'USD')->update(['auto_update' => false]);
});
it('adds four percent only to automatically managed customer rates', function (): void {
    $ngn = Currency::where('code', 'NGN')->firstOrFail();
    $ngn->update(['auto_update' => true, 'is_active' => true]);

    $eur = Currency::where('code', 'EUR')->firstOrFail();
    $eur->update(['auto_update' => false, 'exchange_rate' => 0.95, 'source_exchange_rate' => 0.95]);

    Http::fake([
        '*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'rates' => ['USD' => 1, 'NGN' => 1331.013867, 'EUR' => 0.86],
        ]),
    ]);

    expect(Artisan::call('currencies:sync', ['--force' => true]))->toBe(0);

    expect((float) $ngn->fresh()->source_exchange_rate)->toBe(1331.013867)
        ->and((float) $ngn->fresh()->exchange_rate)->toBe(1384.254422)
        ->and((float) $eur->fresh()->exchange_rate)->toBe(0.95)
        ->and((float) Currency::where('code', 'USD')->value('exchange_rate'))->toBe(1.0)
        ->and(Setting::get('currency.last_synced_at'))->not->toBeNull();
});

it('preserves every previous rate when the provider response is incomplete', function (): void {
    $ngn = Currency::where('code', 'NGN')->firstOrFail();
    $eur = Currency::where('code', 'EUR')->firstOrFail();
    $ngn->update(['auto_update' => true, 'is_active' => true, 'exchange_rate' => 1400]);
    $eur->update(['auto_update' => true, 'is_active' => true, 'exchange_rate' => 0.92]);

    Http::fake([
        '*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'rates' => ['USD' => 1, 'NGN' => 1331.013867],
        ]),
    ]);

    expect(Artisan::call('currencies:sync', ['--force' => true]))->toBe(1)
        ->and((float) $ngn->fresh()->exchange_rate)->toBe(1400.0)
        ->and((float) $eur->fresh()->exchange_rate)->toBe(0.92);
});
