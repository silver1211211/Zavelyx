<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/CurrencySettings', [
            'currencies'          => Currency::orderBy('sort_order')->orderBy('code')->get(),
            'currency_settings'   => [
                'live_rates_enabled'        => (bool) Setting::get('currency.live_rates_enabled', '0'),
                'exchange_api_url'          => Setting::get('currency.exchange_api_url', 'https://open.er-api.com/v6/latest/USD'),
                'exchange_refresh_interval' => (int) Setting::get('currency.exchange_refresh_interval', 30),
                'last_synced_at'            => Setting::get('currency.last_synced_at'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'          => ['required', 'string', 'max:10', 'uppercase', Rule::unique('currencies', 'code')],
            'name'          => ['required', 'string', 'max:100'],
            'symbol'        => ['required', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
            'is_active'     => ['boolean'],
            'sort_order'    => ['integer', 'min:0'],
        ]);

        $data['is_active']  = $data['is_active'] ?? true;
        $data['is_default'] = false;
        $data['sort_order'] = $data['sort_order'] ?? 99;
        $data['code']       = strtoupper($data['code']);

        Currency::create($data);

        return back()->with('success', 'Currency added.');
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $data = $request->validate([
            'code'          => ['required', 'string', 'max:10', Rule::unique('currencies', 'code')->ignore($currency->id)],
            'name'          => ['required', 'string', 'max:100'],
            'symbol'        => ['required', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
            'is_active'     => ['boolean'],
            'sort_order'    => ['integer', 'min:0'],
        ]);

        $data['code'] = strtoupper($data['code']);

        if ($currency->is_default) {
            $data['is_active'] = true;
        }

        $currency->update($data);

        return back()->with('success', 'Currency updated.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return back()->withErrors(['currency' => 'Cannot delete the default currency.']);
        }

        $currency->delete();

        return back()->with('success', 'Currency deleted.');
    }

    public function toggle(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return back()->withErrors(['currency' => 'Cannot deactivate the default currency.']);
        }

        $currency->update(['is_active' => !$currency->is_active]);

        return back()->with('success', 'Currency ' . ($currency->is_active ? 'deactivated' : 'activated') . '.');
    }

    public function setDefault(Currency $currency): RedirectResponse
    {
        Currency::where('is_default', true)->update(['is_default' => false]);
        $currency->update(['is_default' => true, 'is_active' => true]);

        return back()->with('success', $currency->code . ' set as default currency.');
    }

    public function saveCurrencySettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'live_rates_enabled'        => ['boolean'],
            'exchange_api_url'          => ['required', 'url', 'max:500'],
            'exchange_refresh_interval' => ['required', 'integer', 'min:5', 'max:1440'],
        ]);

        Setting::set('currency.live_rates_enabled',        $data['live_rates_enabled'] ? '1' : '0');
        Setting::set('currency.exchange_api_url',          $data['exchange_api_url']);
        Setting::set('currency.exchange_refresh_interval', (string) $data['exchange_refresh_interval']);

        return back()->with('success', 'Currency settings saved.');
    }

    public function refreshRates(Request $request): JsonResponse
    {
        try {
            Artisan::call('currencies:sync', ['--force' => true]);
            $output = Artisan::output();

            $lastSynced = Setting::get('currency.last_synced_at');

            return response()->json([
                'ok'             => true,
                'message'        => trim($output) ?: 'Rates refreshed.',
                'last_synced_at' => $lastSynced,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
