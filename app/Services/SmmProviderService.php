<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SmmProviderService
{
    private const PLATFORM_KEYS = [
        'tiktok', 'youtube', 'telegram', 'spotify', 'crypto', 'google',
        'instagram', 'facebook', 'twitter', 'x', 'twitch', 'website',
        'linkedin', 'soundcloud', 'traffic', 'threads', 'discord', 'seo',
        'reddit', 'pinterest',
    ];

    // ── Connection & balance ──────────────────────────────────────────────────

    public function testConnection(Provider $provider): array
    {
        $creds  = $provider->credentials ?? [];
        $apiKey = $creds['api_key'] ?? '';

        if (empty($apiKey) || empty($provider->base_url)) {
            return ['success' => false, 'message' => 'Missing API URL or API key.'];
        }

        try {
            $response = Http::retry(2, 300)->timeout(15)->asForm()->post($this->endpoint($provider), [
                'key'    => $apiKey,
                'action' => 'balance',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['balance'])) {
                    return [
                        'success' => true,
                        'message' => 'Connected successfully.',
                        'balance' => $data['balance'] . ' ' . ($data['currency'] ?? ''),
                    ];
                }

                if (isset($data['error'])) {
                    $svcResponse = Http::retry(2, 300)->timeout(15)->asForm()->post($this->endpoint($provider), [
                        'key'    => $apiKey,
                        'action' => 'services',
                    ]);

                    if ($svcResponse->successful() && is_array($svcResponse->json())) {
                        return ['success' => true, 'message' => 'Connected (services endpoint verified).'];
                    }

                    return ['success' => false, 'message' => 'API error: ' . $data['error']];
                }
            }

            return ['success' => false, 'message' => 'HTTP ' . $response->status() . ' from provider.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    // ── Order placement ───────────────────────────────────────────────────────

    public function placeOrder(Order $order): array
    {
        $service  = $order->service;
        $provider = $service?->provider;

        if (!$provider || !$provider->is_active) {
            return ['success' => false, 'message' => 'Provider not available.'];
        }

        $creds  = $provider->credentials ?? [];
        $apiKey = $creds['api_key'] ?? '';

        if (empty($apiKey) || empty($provider->base_url)) {
            return ['success' => false, 'message' => 'Provider credentials missing.'];
        }

        $link     = $order->link ?? ($order->payload['link'] ?? '');
        $quantity = $order->quantity ?? ($order->payload['quantity'] ?? 1);

        if (empty($link)) {
            return ['success' => false, 'message' => 'No link provided in order.'];
        }

        try {
            $response = Http::retry(2, 500)->timeout(15)->asForm()->post($this->endpoint($provider), [
                'key'     => $apiKey,
                'action'  => 'add',
                'service' => $service->provider_service_code,
                'link'    => $link,
                'quantity'=> $quantity,
            ]);

            $data = $response->json();

            if (!$response->successful() || !is_array($data)) {
                return ['success' => false, 'message' => 'HTTP ' . $response->status() . ' from provider.'];
            }

            if (isset($data['error'])) {
                return ['success' => false, 'message' => 'Provider error: ' . $data['error']];
            }

            if (isset($data['order'])) {
                return [
                    'success'          => true,
                    'provider_order_id'=> (string) $data['order'],
                ];
            }

            return ['success' => false, 'message' => 'Unexpected provider response.'];
        } catch (Throwable $e) {
            Log::error('SMM placeOrder failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Request failed: ' . $e->getMessage()];
        }
    }

    // ── Order status check ────────────────────────────────────────────────────

    public function checkOrderStatus(Provider $provider, string $providerOrderId): array
    {
        $creds  = $provider->credentials ?? [];
        $apiKey = $creds['api_key'] ?? '';

        Log::channel('orders')->debug('API request: single order status', [
            'provider'       => $provider->name,
            'provider_order' => $providerOrderId,
            'url'            => $provider->base_url,
        ]);

        try {
            $response = Http::retry(2, 500)->timeout(15)->asForm()->post($this->endpoint($provider), [
                'key'    => $apiKey,
                'action' => 'status',
                'order'  => $providerOrderId,
            ]);

            $data = $response->json();

            Log::channel('orders')->debug('API response: single order status', [
                'provider'       => $provider->name,
                'provider_order' => $providerOrderId,
                'http_status'    => $response->status(),
                'response'       => $data,
            ]);

            if (!$response->successful() || !is_array($data)) {
                Log::channel('orders')->warning('API error: bad HTTP response', [
                    'provider'    => $provider->name,
                    'http_status' => $response->status(),
                ]);
                return ['success' => false, 'message' => 'HTTP ' . $response->status()];
            }

            if (isset($data['error'])) {
                Log::channel('orders')->warning('API error: provider returned error', [
                    'provider' => $provider->name,
                    'error'    => $data['error'],
                ]);
                return ['success' => false, 'message' => $data['error']];
            }

            $rawStatus = $data['status'] ?? 'Pending';

            Log::channel('orders')->info('Sync success: single order', [
                'provider'       => $provider->name,
                'provider_order' => $providerOrderId,
                'raw_status'     => $rawStatus,
                'normalized'     => $this->normalizeStatus($rawStatus),
                'start_count'    => $data['start_count'] ?? 0,
                'remains'        => $data['remains'] ?? 0,
            ]);

            return [
                'success'     => true,
                'status'      => $rawStatus,
                'start_count' => (int) ($data['start_count'] ?? 0),
                'remains'     => (int) ($data['remains'] ?? 0),
                'currency'    => $data['currency'] ?? 'USD',
                'raw'         => $data,
            ];
        } catch (Throwable $e) {
            Log::channel('orders')->error('API exception: single order status', [
                'provider'       => $provider->name,
                'provider_order' => $providerOrderId,
                'error'          => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkMultipleOrders(Provider $provider, array $providerOrderIds): array
    {
        return $this->checkMultipleOrdersLogged($provider, $providerOrderIds);
    }

    public function checkMultipleOrdersLogged(Provider $provider, array $providerOrderIds): array
    {
        $creds  = $provider->credentials ?? [];
        $apiKey = $creds['api_key'] ?? '';

        Log::channel('orders')->debug('API request: batch order status', [
            'provider'   => $provider->name,
            'url'        => $provider->base_url,
            'order_ids'  => $providerOrderIds,
            'count'      => count($providerOrderIds),
        ]);

        try {
            $response = Http::retry(2, 500)->timeout(15)->asForm()->post($this->endpoint($provider), [
                'key'    => $apiKey,
                'action' => 'status',
                'orders' => implode(',', $providerOrderIds),
            ]);

            $data = $response->json();

            Log::channel('orders')->debug('API response: batch order status', [
                'provider'    => $provider->name,
                'http_status' => $response->status(),
                'result_count' => is_array($data) ? count($data) : 0,
            ]);

            if (!$response->successful() || !is_array($data)) {
                Log::channel('orders')->warning('Batch API error: bad response', [
                    'provider'    => $provider->name,
                    'http_status' => $response->status(),
                ]);
                return [];
            }

            Log::channel('orders')->info('Sync success: batch status', [
                'provider'    => $provider->name,
                'count'       => count($data),
            ]);

            return $data;
        } catch (Throwable $e) {
            Log::channel('orders')->error('API exception: batch order status', [
                'provider' => $provider->name,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ── Service sync ──────────────────────────────────────────────────────────

    public function fetchServices(Provider $provider): array
    {
        $creds  = $provider->credentials ?? [];
        $apiKey = $creds['api_key'] ?? '';

        if (empty($apiKey) || empty($provider->base_url)) {
            throw new \RuntimeException('Missing API URL or API key.');
        }

        $response = Http::retry(2, 750)->timeout(15)->asForm()->post($this->endpoint($provider), [
            'key'    => $apiKey,
            'action' => 'services',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP ' . $response->status() . ' from provider.');
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid response from provider.');
        }

        if (isset($data['error'])) {
            throw new \RuntimeException('API error: ' . $data['error']);
        }

        return $this->normalizeServicesResponse($data);
    }

    public function importServices(Provider $provider): array
    {
        $raw = $this->fetchServices($provider);

        $markupType  = $provider->markup_type ?? 'percentage';
        $markupValue = (float) ($provider->markup_value ?? 0);

        $imported      = 0;
        $updated       = 0;
        $deactivated   = 0;
        $categoryCache = [];
        $seenCodes     = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $serviceId    = $item['service'] ?? $item['id'] ?? $item['service_id'] ?? null;
            $name         = trim((string) ($item['name'] ?? ''));
            $categoryName = trim((string) ($item['category'] ?? '')) ?: 'Uncategorized';

            if (!$serviceId || !$name) {
                continue;
            }

            $seenCodes[] = (string) $serviceId;
            $costPrice    = (float) ($item['rate'] ?? 0);
            $sellingPrice = $this->applyMarkup($costPrice, $markupType, $markupValue);
            $platform     = $this->inferPlatform($name, $categoryName);
            $isActive     = $this->providerServiceIsActive($item);

            if (!array_key_exists($categoryName, $categoryCache)) {
                $baseSlug = Str::slug($categoryName) ?: ('category-' . Str::random(6));
                $slug     = $baseSlug;
                $suffix   = 2;
                while (\App\Models\Category::where('slug', $slug)
                                           ->where('name', '!=', $categoryName)
                                           ->exists()) {
                    $slug = $baseSlug . '-' . $suffix++;
                }

                $categoryCache[$categoryName] = Category::firstOrCreate(
                    ['name' => $categoryName],
                    ['slug' => $slug, 'type' => 'smm', 'is_active' => true]
                );
            }

            $categoryModel = $categoryCache[$categoryName];
            $serviceSlug   = Str::slug($name) . '-' . $provider->id . '-' . $serviceId;

            $payload = [
                'name'                  => $name,
                'slug'                  => $serviceSlug,
                'category_id'           => $categoryModel->id,
                'provider_id'           => $provider->id,
                'provider_service_code' => (string) $serviceId,
                'type'                  => 'smm',
                'cost_price'            => $costPrice,
                'selling_price'         => $sellingPrice,
                'min_amount'            => (float) ($item['min'] ?? 1),
                'max_amount'            => (float) ($item['max'] ?? 1000000),
                'metadata'              => [
                    'refill'          => $this->truthy($item['refill'] ?? false),
                    'cancel'          => $this->truthy($item['cancel'] ?? false),
                    'platform'        => $platform,
                    'provider_status' => $item['status'] ?? null,
                ],
                'is_active'             => $isActive,
            ];

            $matches = Service::where('provider_id', $provider->id)
                ->where('provider_service_code', (string) $serviceId)
                ->orderBy('id')
                ->get();

            $existing = $matches->first();

            if ($existing) {
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

        if ($seenCodes !== []) {
            $deactivated = Service::where('provider_id', $provider->id)
                ->whereNotIn('provider_service_code', array_unique($seenCodes))
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $provider->update(['last_synced_at' => now()]);
        $this->clearUserServiceCaches();

        return [
            'imported' => $imported,
            'updated' => $updated,
            'deactivated' => $deactivated,
            'total' => $imported + $updated,
        ];
    }

    public function recalculateMarkup(Provider $provider): int
    {
        $markupType  = $provider->markup_type ?? 'percentage';
        $markupValue = (float) ($provider->markup_value ?? 0);
        $updated     = 0;

        $provider->services()->whereNotNull('cost_price')->chunkById(500, function ($chunk) use ($markupType, $markupValue, &$updated) {
            foreach ($chunk as $service) {
                $newPrice = $this->applyMarkup((float) $service->cost_price, $markupType, $markupValue);
                $service->update(['selling_price' => $newPrice]);
                $updated++;
            }
        });

        $this->clearUserServiceCaches();

        return $updated;
    }

    public function clearUserServiceCaches(): void
    {
        Cache::forget('dash_smm_platforms');
        Cache::forget('dash_popular_services');
        Cache::forget('services.index.smm');
        Cache::forget('order.categories');
        Cache::forget('order.platform_counts');
        Cache::forget('order.svcs.all');

        foreach (self::PLATFORM_KEYS as $platform) {
            Cache::forget("order.svcs.{$platform}");
        }
    }

    private function endpoint(Provider $provider): string
    {
        return rtrim((string) $provider->base_url, " \t\n\r\0\x0B/");
    }

    private function normalizeServicesResponse(array $data): array
    {
        if (isset($data['error'])) {
            throw new \RuntimeException('API error: ' . $data['error']);
        }

        foreach (['services', 'data', 'result'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data = $data[$key];
                break;
            }
        }

        if (!array_is_list($data)) {
            $data = array_values($data);
        }

        return array_values(array_filter($data, fn ($row) => is_array($row)));
    }

    private function providerServiceIsActive(array $item): bool
    {
        if (!array_key_exists('status', $item)) {
            return true;
        }

        $status = strtolower(trim((string) $item['status']));

        return !in_array($status, ['0', 'false', 'inactive', 'disabled', 'off', 'unavailable'], true);
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    private function inferPlatform(string $name, string $category): ?string
    {
        $haystack = strtolower($category . ' ' . $name);

        foreach (self::PLATFORM_KEYS as $key) {
            if (str_contains($haystack, $key)) {
                return $key === 'x' ? 'twitter' : $key;
            }
        }

        return null;
    }

    private function applyMarkup(float $cost, string $type, float $value): float
    {
        return $type === 'fixed' ? $cost + $value : $cost * (1 + $value / 100);
    }

    // ── Normalize provider status to our internal statuses ───────────────────

    public function normalizeStatus(string $providerStatus): string
    {
        return match (strtolower(trim($providerStatus))) {
            // Completed
            'completed', 'complete', 'success', 'done', 'delivered' => 'completed',

            // Processing / In progress
            'in progress', 'inprogress', 'in_progress',
            'processing', 'active', 'running', 'started' => 'processing',

            // Partial
            'partial', 'partially completed', 'partially_completed' => 'partial',

            // Canceled
            'canceled', 'cancelled', 'cancel',
            'refunded', 'refund' => 'canceled',

            // Failed / Error
            'failed', 'error', 'fail', 'rejected',
            'not found', 'notfound', 'invalid',
            'insufficient funds' => 'failed',

            // Pending (explicit)
            'pending', 'queued', 'waiting',
            'in queue', 'inqueue', 'new' => 'pending',

            default => 'pending',
        };
    }
}
