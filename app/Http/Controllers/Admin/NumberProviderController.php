<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\NumberProvider;
use App\Models\Service;
use App\Services\NumberProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NumberProviderController extends Controller
{
    public function __construct(private NumberProviderService $providerService) {}

    public function index(): Response
    {
        $providers = NumberProvider::orderBy('priority')->get()->map(fn ($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'driver'           => $p->driver,
            'base_url'         => $p->getBaseUrl(),
            'markup_percent'   => (float) $p->markup_percent,
            'priority'         => $p->priority,
            'is_active'        => $p->is_active,
            'last_synced_at'   => $p->last_synced_at?->toIso8601String(),
            'last_tested_at'   => $p->last_tested_at?->toIso8601String(),
            'last_test_result' => $p->last_test_result,
            'created_at'       => $p->created_at->toIso8601String(),
            'has_api_key'      => !empty($p->getApiKey()),
        ]);

        return Inertia::render('Admin/NumberProviders', ['providers' => $providers]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'driver'         => ['required', 'in:fivesim,pvapins,smspva'],
            'api_key'        => ['required', 'string'],
            'base_url'       => ['nullable', 'url', 'max:500'],
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:500'],
            'priority'       => ['required', 'integer', 'min:1'],
        ]);

        $credentials = ['api_key' => $validated['api_key']];
        if (!empty($validated['base_url'])) {
            $credentials['base_url'] = $validated['base_url'];
        }

        NumberProvider::create([
            'name'           => $validated['name'],
            'driver'         => $validated['driver'],
            'credentials'    => $credentials,
            'markup_percent' => $validated['markup_percent'],
            'priority'       => $validated['priority'],
        ]);

        $this->bustAllCaches();

        return back()->with('flash_success', 'Provider added.');
    }

    public function update(Request $request, NumberProvider $numberProvider): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:500'],
            'priority'       => ['required', 'integer', 'min:1'],
            'api_key'        => ['nullable', 'string'],
            'base_url'       => ['nullable', 'url', 'max:500'],
        ]);

        $data = [
            'name'           => $validated['name'],
            'markup_percent' => $validated['markup_percent'],
            'priority'       => $validated['priority'],
        ];

        if (!empty($validated['api_key']) || isset($validated['base_url'])) {
            $credentials = $numberProvider->credentials ?? [];
            if (!empty($validated['api_key'])) {
                $credentials['api_key'] = $validated['api_key'];
            }
            if (isset($validated['base_url'])) {
                if ($validated['base_url']) {
                    $credentials['base_url'] = $validated['base_url'];
                } else {
                    unset($credentials['base_url']);
                }
            }
            $data['credentials'] = $credentials;
        }

        $numberProvider->update($data);

        $this->bustAllCaches();

        return back()->with('flash_success', 'Provider updated.');
    }

    public function destroy(NumberProvider $numberProvider): RedirectResponse
    {
        $numberProvider->delete();
        $this->bustAllCaches();
        return back()->with('flash_success', 'Provider deleted.');
    }

    public function toggle(NumberProvider $numberProvider): JsonResponse
    {
        $numberProvider->update(['is_active' => !$numberProvider->is_active]);
        $this->bustAllCaches();
        return response()->json(['is_active' => $numberProvider->is_active]);
    }

    public function test(NumberProvider $numberProvider): JsonResponse
    {
        $startedAt = now();

        try {
            $driver  = $this->providerService->driver($numberProvider);
            $balance = $driver->getBalance();

            $result = [
                'ok'        => true,
                'balance'   => $balance,
                'tested_at' => $startedAt->toIso8601String(),
                'error'     => null,
            ];

            $numberProvider->update([
                'last_test_result' => $result,
                'last_tested_at'   => $startedAt,
            ]);

            return response()->json($result);

        } catch (\Throwable $e) {
            $message = $e->getMessage();

            $result = [
                'ok'        => false,
                'error'     => $this->friendlyError($message),
                'raw_error' => $message,
                'tested_at' => $startedAt->toIso8601String(),
                'balance'   => null,
            ];

            $numberProvider->update([
                'last_test_result' => $result,
                'last_tested_at'   => $startedAt,
            ]);

            return response()->json($result, 422);
        }
    }

    public function sync(NumberProvider $numberProvider): JsonResponse
    {
        @set_time_limit(900);
        $previousMemoryLimit = null;
        $lock = Cache::lock("number_provider_sync_{$numberProvider->id}", 900);

        if (!$lock->get()) {
            return response()->json([
                'ok' => false,
                'error' => 'A sync is already running for this provider. Please wait for it to finish.',
            ], 409);
        }

        try {
            $driver   = $this->providerService->driver($numberProvider);
            $effectiveDriver = $this->providerService->effectiveDriver($numberProvider);

            if ($effectiveDriver === 'fivesim') {
                $previousMemoryLimit = ini_get('memory_limit');
                ini_set('memory_limit', '512M');
            }

            $products = $driver->getAllProducts();
            $count    = count($products);

            if ($count === 0) {
                Log::warning('Number provider sync returned empty product list after adapter parsing', [
                    'number_provider_id' => $numberProvider->id,
                    'provider' => $numberProvider->name,
                    'driver' => $this->providerService->effectiveDriver($numberProvider),
                    'base_url' => $numberProvider->getBaseUrl(),
                    'last_test_ok' => (bool) ($numberProvider->last_test_result['ok'] ?? false),
                ]);

                return response()->json([
                    'ok'      => false,
                    'products' => 0,
                    'error'   => 'Provider returned 0 products after a successful response was parsed. This may mean the provider catalog is truly empty for this account, or the provider returned an unexpected catalog format. Check logs for the safe raw response preview.',
                ], 422);
            }

            $import = match ($effectiveDriver) {
                'pvapins' => $this->importPvaPinsProducts($numberProvider, $products),
                'fivesim' => $this->importFiveSimProducts($numberProvider, $products),
                default => $this->importProducts($numberProvider, $products),
            };

            $numberProvider->update(['last_synced_at' => now()]);

            $this->bustAllCaches();

            return response()->json([
                'ok'        => true,
                'products'  => $count,
                'imported'  => $import['imported'],
                'updated'   => $import['updated'],
                'deactivated' => $import['deactivated'],
                'synced_at' => $numberProvider->fresh()->last_synced_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $this->friendlyError($e->getMessage())], 422);
        } finally {
            unset($products);
            gc_collect_cycles();
            if ($previousMemoryLimit !== null) {
                ini_set('memory_limit', (string) $previousMemoryLimit);
            }
            optional($lock)->release();
        }
    }

    private function bustAllCaches(): void
    {
        $this->refreshSmsCountrySummaries();
        Cache::increment('nexahub_sms_v');
        Cache::forget('sms_number_services');
    }

    private function refreshSmsCountrySummaries(): void
    {
        if (!Schema::hasTable('sms_country_summaries')) {
            return;
        }

        DB::table('sms_country_summaries')->truncate();
        DB::statement(<<<'SQL'
            INSERT INTO sms_country_summaries (code, name, qty, created_at, updated_at)
            SELECT
                sms_country,
                COALESCE(NULLIF(MAX(sms_country_name), ''), sms_country) as name,
                SUM(sms_available_count) as qty,
                NOW(),
                NOW()
            FROM services
            WHERE type = 'sms'
              AND is_active = 1
              AND sms_country IS NOT NULL
              AND sms_country <> ''
              AND sms_country <> 'any'
            GROUP BY sms_country
        SQL);
    }

    private function importProducts(NumberProvider $numberProvider, array $products): array
    {
        $categoryCache = [];
        $seenCodes = [];
        $imported = 0;
        $updated = 0;

        foreach ($products as $code => $info) {
            if (!is_array($info)) {
                continue;
            }

            $providerCode = (string) $code;
            $name = $this->productName($providerCode, $info);
            if ($providerCode === '' || $name === '') {
                continue;
            }

            $qty = (int) ($info['Qty'] ?? $info['qty'] ?? $info['count'] ?? $info['stock'] ?? 0);
            $cost = (float) ($info['Price'] ?? $info['price'] ?? $info['Cost'] ?? $info['cost'] ?? $info['rate'] ?? 0);
            $categoryName = trim((string) ($info['Category'] ?? $info['category'] ?? 'SMS Activation')) ?: 'SMS Activation';
            $isPvaPins = $this->providerService->effectiveDriver($numberProvider) === 'pvapins';
            $isFiveSim = $this->providerService->effectiveDriver($numberProvider) === 'fivesim';
            $seenCodes[] = $providerCode;

            if (!isset($categoryCache[$categoryName])) {
                $slug = Str::slug('SMS ' . $categoryName) ?: 'sms-activation';
                $categoryCache[$categoryName] = Category::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $categoryName, 'type' => 'sms', 'is_active' => true]
                );
            }

            $sellingPrice = $numberProvider->applyMarkup($cost);
            $smsCountry = $info['country'] ?? null;
            $smsCountryName = $info['country_name'] ?? null;
            $smsOperator = $info['operator'] ?? null;
            $driverName = $this->providerService->effectiveDriver($numberProvider);
            $payload = [
                'category_id' => $categoryCache[$categoryName]->id,
                'provider_id' => null,
                'name' => $name,
                'slug' => $this->uniqueServiceSlug($numberProvider, $name, $providerCode),
                'type' => 'sms',
                'provider_service_code' => $providerCode,
                'cost_price' => $cost,
                'selling_price' => $sellingPrice,
                'min_amount' => 1,
                'max_amount' => max(1, $qty),
                'sms_country' => $smsCountry,
                'sms_country_name' => $smsCountryName,
                'sms_operator' => $smsOperator,
                'sms_available_count' => max(0, $qty),
                'number_provider_driver' => $driverName,
                'metadata' => [
                    'number_provider_id' => $numberProvider->id,
                    'number_provider_driver' => $driverName,
                    'provider_product_id' => (string) ($info['id'] ?? $info['product_id'] ?? $providerCode),
                    'country' => $smsCountry,
                    'country_id' => $info['country_id'] ?? null,
                    'country_name' => $smsCountryName,
                    'service' => $isPvaPins
                        ? ($info['app_name'] ?? $info['name'] ?? $providerCode)
                        : ($isFiveSim ? ($info['product'] ?? $providerCode) : $providerCode),
                    'operator' => $smsOperator,
                    'provider_product_name' => $info['product'] ?? null,
                    'provider_app_id' => $info['app_id'] ?? null,
                    'provider_app_name' => $info['app_name'] ?? $info['full_name'] ?? $info['name'] ?? null,
                    'provider_price_deduct' => $info['deduct'] ?? $cost,
                    'provider_trending' => (int) ($info['trending'] ?? 0),
                    'provider_rate' => (float) ($info['rate'] ?? 0),
                    'available_count' => $qty,
                    'provider_status' => $qty > 0 ? 'available' : 'empty',
                ],
                'is_active' => $qty > 0,
            ];

            $matches = Service::where('type', 'sms')
                ->where('provider_service_code', $providerCode)
                ->where('metadata->number_provider_id', $numberProvider->id)
                ->orderBy('id')
                ->get();

            $existing = $matches->first();

            if ($existing) {
                $payload['slug'] = $existing->slug;
                $existing->update($payload);
                $matches->skip(1)->each(function (Service $duplicate): void {
                    if ($duplicate->orders()->exists()) {
                        $duplicate->update(['is_active' => false]);
                    } else {
                        $duplicate->delete();
                    }
                });
                $updated++;
            } else {
                Service::create($payload);
                $imported++;
            }
        }

        $deactivated = 0;
        if ($seenCodes !== []) {
            $deactivated = Service::where('type', 'sms')
                ->where('metadata->number_provider_id', $numberProvider->id)
                ->whereNotIn('provider_service_code', array_unique($seenCodes))
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return compact('imported', 'updated', 'deactivated');
    }

    private function importPvaPinsProducts(NumberProvider $numberProvider, array $products): array
    {
        $category = Category::firstOrCreate(
            ['slug' => 'sms-pvapins-activation'],
            ['name' => 'PVAPins Activation', 'type' => 'sms', 'is_active' => true]
        );

        $now = now();
        $existingTotal = Service::where('type', 'sms')
            ->where('metadata->number_provider_id', $numberProvider->id)
            ->count();
        $processed = 0;
        $deactivated = 0;

        $flush = function (array $chunk) use ($numberProvider, $category, $now, &$processed): void {
            $rows = [];
            foreach ($chunk as $code => $info) {
                if (!is_array($info)) {
                    continue;
                }

                $providerCode = (string) $code;
                $name = $this->productName($providerCode, $info);
                if ($providerCode === '' || $name === '') {
                    continue;
                }

                $qty = (int) ($info['Qty'] ?? $info['qty'] ?? $info['count'] ?? $info['stock'] ?? 0);
                $cost = (float) ($info['Price'] ?? $info['price'] ?? $info['Cost'] ?? $info['cost'] ?? $info['deduct'] ?? 0);
                $countryId = (string) ($info['country_id'] ?? '');
                $country = $info['country'] ?? null;
                $countryName = $info['country_name'] ?? null;
                $appId = (string) ($info['app_id'] ?? $info['id'] ?? md5($providerCode));
                $slug = Str::slug("sms pvapins {$numberProvider->id} {$countryId} {$appId}");
                $slug = $slug ?: ('sms-pvapins-' . $numberProvider->id . '-' . md5($providerCode));

                $metadata = [
                    'number_provider_id' => $numberProvider->id,
                    'number_provider_driver' => 'pvapins',
                    'provider_product_id' => (string) ($info['id'] ?? $providerCode),
                    'country' => $country,
                    'country_id' => $countryId ?: null,
                    'country_name' => $countryName,
                    'service' => $info['app_name'] ?? $name,
                    'provider_app_id' => $appId,
                    'provider_app_name' => $info['app_name'] ?? $name,
                    'provider_price_deduct' => $info['deduct'] ?? $cost,
                    'provider_trending' => (int) ($info['trending'] ?? 0),
                    'available_count' => $qty,
                    'provider_status' => $qty > 0 ? 'available' : 'empty',
                ];

                $rows[] = [
                    'category_id' => $category->id,
                    'provider_id' => null,
                    'name' => $name,
                    'slug' => $slug,
                    'type' => 'sms',
                    'provider_service_code' => $providerCode,
                    'cost_price' => $cost,
                    'selling_price' => $numberProvider->applyMarkup($cost),
                    'min_amount' => 1,
                    'max_amount' => max(1, $qty),
                    'metadata' => json_encode($metadata),
                    'sms_country' => $country,
                    'sms_country_name' => $countryName,
                    'sms_operator' => 'any',
                    'sms_available_count' => max(0, $qty),
                    'number_provider_driver' => 'pvapins',
                    'is_active' => $qty > 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $processed++;
            }

            if ($rows !== []) {
                Service::upsert($rows, ['slug'], [
                    'category_id',
                    'provider_id',
                    'name',
                    'type',
                    'provider_service_code',
                    'cost_price',
                    'selling_price',
                    'min_amount',
                    'max_amount',
                    'metadata',
                    'sms_country',
                    'sms_country_name',
                    'sms_operator',
                    'sms_available_count',
                    'number_provider_driver',
                    'is_active',
                    'updated_at',
                ]);
            }
        };

        $chunk = [];
        foreach ($products as $code => $info) {
            $chunk[$code] = $info;
            if (count($chunk) >= 100) {
                $flush($chunk);
                $chunk = [];
                gc_collect_cycles();
            }
        }

        if ($chunk !== []) {
            $flush($chunk);
        }

        $updated = min($processed, $existingTotal);
        $imported = max(0, $processed - $existingTotal);

        return compact('imported', 'updated', 'deactivated');
    }

    private function importFiveSimProducts(NumberProvider $numberProvider, array $products): array
    {
        $category = Category::firstOrCreate(
            ['slug' => 'sms-5sim-activation'],
            ['name' => '5SIM Activation', 'type' => 'sms', 'is_active' => true]
        );

        $now = now();
        $imported = 0;
        $updated = 0;
        $deactivated = Service::where('type', 'sms')
            ->where('metadata->number_provider_id', $numberProvider->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $flush = function (array $chunk) use ($numberProvider, $category, $now, &$imported, &$updated): void {
            $existingCodes = Service::where('type', 'sms')
                ->whereIn('provider_service_code', array_map('strval', array_keys($chunk)))
                ->pluck('provider_service_code')
                ->map(fn ($code) => (string) $code)
                ->all();
            $existingMap = array_flip($existingCodes);

            $rows = [];
            foreach ($chunk as $code => $info) {
                if (!is_array($info)) {
                    continue;
                }

                $providerCode = (string) $code;
                $name = $this->productName($providerCode, $info);
                if ($providerCode === '' || $name === '') {
                    continue;
                }

                $qty = (int) ($info['Qty'] ?? $info['qty'] ?? $info['count'] ?? 0);
                $cost = (float) ($info['Price'] ?? $info['price'] ?? $info['cost'] ?? 0);
                $country = (string) ($info['country'] ?? 'any');
                $operator = (string) ($info['operator'] ?? 'any');
                $product = (string) ($info['product'] ?? $providerCode);
                $slug = Str::slug("sms 5sim {$numberProvider->id} {$country} {$operator} {$product}");
                $slug = $slug ?: ('sms-5sim-' . $numberProvider->id . '-' . md5($providerCode));

                $metadata = [
                    'number_provider_id' => $numberProvider->id,
                    'number_provider_driver' => 'fivesim',
                    'provider_product_id' => $providerCode,
                    'provider_product_name' => $product,
                    'country' => $country,
                    'country_name' => $info['country_name'] ?? $country,
                    'country_iso' => $info['country_iso'] ?? null,
                    'country_prefix' => $info['country_prefix'] ?? null,
                    'operator' => $operator,
                    'service' => $product,
                    'available_count' => $qty,
                    'provider_rate' => (float) ($info['rate'] ?? 0),
                    'provider_status' => $qty > 0 ? 'available' : 'empty',
                ];

                $rows[] = [
                    'category_id' => $category->id,
                    'provider_id' => null,
                    'name' => $name,
                    'slug' => $slug,
                    'type' => 'sms',
                    'provider_service_code' => $providerCode,
                    'cost_price' => $cost,
                    'selling_price' => $numberProvider->applyMarkup($cost),
                    'min_amount' => 1,
                    'max_amount' => max(1, $qty),
                    'metadata' => json_encode($metadata),
                    'sms_country' => $country,
                    'sms_country_name' => $info['country_name'] ?? $country,
                    'sms_operator' => $operator,
                    'sms_available_count' => max(0, $qty),
                    'number_provider_driver' => 'fivesim',
                    'is_active' => $qty > 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (isset($existingMap[$providerCode])) {
                    $updated++;
                } else {
                    $imported++;
                }
            }

            if ($rows !== []) {
                Service::upsert($rows, ['slug'], [
                    'category_id',
                    'provider_id',
                    'name',
                    'type',
                    'provider_service_code',
                    'cost_price',
                    'selling_price',
                    'min_amount',
                    'max_amount',
                    'metadata',
                    'sms_country',
                    'sms_country_name',
                    'sms_operator',
                    'sms_available_count',
                    'number_provider_driver',
                    'is_active',
                    'updated_at',
                ]);
            }
        };

        $chunk = [];
        foreach ($products as $code => $info) {
            $chunk[$code] = $info;
            if (count($chunk) >= 500) {
                $flush($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $flush($chunk);
        }

        $this->deactivateDuplicateFiveSimRows($numberProvider);

        return compact('imported', 'updated', 'deactivated');
    }

    private function deactivateDuplicateFiveSimRows(NumberProvider $numberProvider): void
    {
        Service::query()
            ->select('provider_service_code')
            ->where('type', 'sms')
            ->where('is_active', true)
            ->where('provider_service_code', 'like', '5sim:%')
            ->where('metadata->number_provider_id', $numberProvider->id)
            ->groupBy('provider_service_code')
            ->havingRaw('count(*) > 1')
            ->pluck('provider_service_code')
            ->chunk(500)
            ->each(function ($codes) use ($numberProvider): void {
                foreach ($codes as $code) {
                    $ids = Service::where('type', 'sms')
                        ->where('is_active', true)
                        ->where('provider_service_code', $code)
                        ->where('metadata->number_provider_id', $numberProvider->id)
                        ->orderBy('id')
                        ->pluck('id')
                        ->all();

                    $keep = array_shift($ids);
                    if ($keep && $ids !== []) {
                        Service::whereIn('id', $ids)->update(['is_active' => false]);
                    }
                }
            });
    }

    private function productName(string $code, array $info): string
    {
        $name = trim((string) ($info['name'] ?? $info['full_name'] ?? $info['label'] ?? ''));
        return $name !== '' ? $name : ucwords(str_replace(['-', '_'], ' ', $code));
    }

    private function uniqueServiceSlug(NumberProvider $numberProvider, string $name, string $code): string
    {
        $base = Str::slug('sms ' . $numberProvider->id . ' ' . $name . ' ' . $code);
        $base = $base ?: ('sms-' . $numberProvider->id . '-' . md5($code));
        $slug = $base;
        $suffix = 2;

        while (Service::where('slug', $slug)
            ->where(function ($query) use ($numberProvider, $code): void {
                $query->where('type', '!=', 'sms')
                    ->orWhere('provider_service_code', '!=', $code)
                    ->orWhere('metadata->number_provider_id', '!=', $numberProvider->id);
            })
            ->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function friendlyError(string $raw): string
    {
        if (str_contains($raw, '401') || str_contains(strtolower($raw), 'unauthorized')) {
            return 'Invalid API key — authentication failed (401).';
        }
        if (str_contains($raw, '403') || str_contains(strtolower($raw), 'forbidden')) {
            return 'API key does not have required permissions (403).';
        }
        if (str_contains($raw, '429') || str_contains(strtolower($raw), 'rate limit')) {
            return 'Rate limit exceeded — too many requests. Try again in a minute.';
        }
        if (str_contains($raw, '500') || str_contains($raw, '502') || str_contains($raw, '503')) {
            return 'Provider API is unavailable (server error). Try again later.';
        }
        if (str_contains(strtolower($raw), 'connect') || str_contains(strtolower($raw), 'timeout')) {
            return 'Connection timed out — check your network or the provider API is down.';
        }
        if (str_contains(strtolower($raw), 'ssl') || str_contains(strtolower($raw), 'certificate')) {
            return 'SSL/TLS error connecting to provider API.';
        }
        if (strlen($raw) > 150) {
            return substr($raw, 0, 150) . '…';
        }
        return $raw;
    }
}
