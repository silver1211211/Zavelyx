<?php

namespace App\Services;

use App\Contracts\NumberProviderContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FiveSimService implements NumberProviderContract
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout = 15;
    private int $connectTimeout = 6;

    public function __construct(string $apiKey, string $baseUrl = 'https://5sim.net')
    {
        $this->apiKey = trim($apiKey);
        $baseUrl = trim($baseUrl);

        if ($baseUrl === '') {
            $baseUrl = 'https://5sim.net';
        }

        $baseUrl = rtrim($baseUrl, '/');
        if (!str_ends_with($baseUrl, '/v1')) {
            $baseUrl .= '/v1';
        }

        $this->baseUrl = $baseUrl;
    }

    private function cv(): string
    {
        return (string) Cache::get('nexahub_sms_v', 0);
    }

    public static function bustCache(): void
    {
        Cache::increment('nexahub_sms_v');
    }

    private function endpoint(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    private function get(string $path, array $query = [], bool $auth = true): array
    {
        try {
            $request = Http::acceptJson()
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout);

            if ($auth) {
                $request = $request->withToken($this->apiKey);
            }

            $response = $request->get($this->endpoint($path), $query);
            $body = trim($response->body());

            Log::debug('5SIM HTTP response', [
                'endpoint' => $path,
                'query' => $query,
                'status' => $response->status(),
                'preview' => mb_substr($body, 0, 500),
            ]);

            if ($response->status() === 401) {
                throw new \RuntimeException('5SIM invalid API token.');
            }

            if ($response->status() === 403) {
                throw new \RuntimeException('5SIM API token does not have required permissions.');
            }

            if ($response->status() === 429) {
                throw new \RuntimeException('5SIM rate limit exceeded.');
            }

            if ($response->failed()) {
                $this->throwProviderTextError($body);
                throw new \RuntimeException("5SIM HTTP {$response->status()} from {$path}.");
            }

            $json = $response->json();
            if ($json === null) {
                $this->throwProviderTextError($body);
                throw new \RuntimeException("5SIM returned an unexpected non-JSON response from {$path}.");
            }

            return is_array($json) ? $json : ['_raw' => $json];
        } catch (ConnectionException $e) {
            throw new \RuntimeException('5SIM provider unreachable or timed out: ' . $e->getMessage());
        }
    }

    private function throwProviderTextError(string $body): void
    {
        $lower = strtolower($body);

        if ($lower === '') {
            throw new \RuntimeException('5SIM returned an empty response.');
        }

        if (str_contains($lower, 'not enough user balance')) {
            throw new \RuntimeException('5SIM account balance is too low for this request.');
        }

        if (str_contains($lower, 'no free phones')) {
            throw new \RuntimeException('5SIM has no free phones for this country/operator/product.');
        }

        if (str_contains($lower, 'bad country')) {
            throw new \RuntimeException('5SIM rejected the selected country.');
        }

        if (str_contains($lower, 'bad operator')) {
            throw new \RuntimeException('5SIM rejected the selected operator.');
        }

        if (str_contains($lower, 'no product') || str_contains($lower, 'product is incorrect')) {
            throw new \RuntimeException('5SIM rejected the selected product.');
        }

        if (str_contains($lower, 'server offline')) {
            throw new \RuntimeException('5SIM server is temporarily offline.');
        }
    }

    public function getBalance(): float
    {
        $data = $this->get('/user/profile');
        return (float) ($data['balance'] ?? 0);
    }

    public function testConnection(): bool
    {
        try {
            $this->get('/user/profile');
            return true;
        } catch (\Throwable $e) {
            Log::warning('5SIM connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getCountries(): array
    {
        return Cache::remember('5sim_countries_' . $this->cv(), 3600, function () {
            return $this->get('/guest/countries', [], false);
        });
    }

    public function getAllProducts(): array
    {
        $cacheKey = '5sim_prices_catalog_' . $this->cv();
        $cached = Cache::get($cacheKey);
        if ($cached !== null && $cached !== []) {
            return $cached;
        }

        $countries = $this->getCountries();
        $prices = $this->get('/guest/prices', [], false);
        $catalog = $this->flattenPrices($prices, $countries);

        if ($catalog === []) {
            Log::warning('5SIM /guest/prices parsed to 0 products.', [
                'top_keys' => array_slice(array_keys($prices), 0, 10),
            ]);
            return [];
        }

        Cache::put($cacheKey, $catalog, 900);
        return $catalog;
    }

    public function getPrices(string $product): array
    {
        $product = $this->decodeProduct($product);
        $cacheKey = '5sim_prices_' . md5($product) . '_' . $this->cv();
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->get('/guest/prices', ['product' => $product], false);
        $prices = $this->normalizeProductPrices($data, $product);

        if ($prices !== []) {
            Cache::put($cacheKey, $prices, 600);
        }

        return $prices;
    }

    public function getAllProductsByCountry(string $country): array
    {
        $prices = $this->get('/guest/prices', ['country' => $country], false);
        $countries = $this->getCountries();
        $countryPrices = isset($prices[$country]) && is_array($prices[$country])
            ? $prices[$country]
            : $prices;

        return $this->flattenPrices([$country => $countryPrices], $countries);
    }

    public function getProductsForCountries(array $countries): array
    {
        $out = [];
        foreach ($countries as $country) {
            $out[$country] = $this->getAllProductsByCountry((string) $country);
        }

        return $out;
    }

    public function buyNumber(string $country, string $operator, string $service): array
    {
        $product = $this->decodeProduct($service);
        $country = $country ?: 'any';
        $operator = $operator ?: 'any';

        Log::info('5SIM buyNumber', [
            'country' => $country,
            'operator' => $operator,
            'product' => $product,
        ]);

        $data = $this->get('/user/buy/activation/' . rawurlencode($country) . '/' . rawurlencode($operator) . '/' . rawurlencode($product));

        if (empty($data['id']) || empty($data['phone'])) {
            throw new \RuntimeException('5SIM returned an unexpected buy response.');
        }

        return [
            'id' => (string) $data['id'],
            'phone' => (string) $data['phone'],
            'operator' => (string) ($data['operator'] ?? $operator),
            'status' => (string) ($data['status'] ?? 'PENDING'),
            'expires' => $data['expires'] ?? now()->addMinutes(15)->toIso8601String(),
            'sms' => is_array($data['sms'] ?? null) ? $data['sms'] : [],
            'price' => (float) ($data['price'] ?? 0),
            'country' => (string) ($data['country'] ?? $country),
            'product' => (string) ($data['product'] ?? $product),
            'raw' => $data,
        ];
    }

    public function checkOrder(int|string $orderId): array
    {
        $data = $this->get('/user/check/' . rawurlencode((string) $orderId));
        $sms = [];

        foreach (($data['sms'] ?? []) as $message) {
            if (!is_array($message)) {
                continue;
            }

            $sms[] = [
                'text' => (string) ($message['text'] ?? ''),
                'sender' => $message['sender'] ?? null,
                'created_at' => $message['created_at'] ?? $message['date'] ?? now()->toIso8601String(),
            ];
        }

        return [
            'id' => (string) ($data['id'] ?? $orderId),
            'phone' => (string) ($data['phone'] ?? ''),
            'status' => (string) ($data['status'] ?? 'PENDING'),
            'expires' => $data['expires'] ?? null,
            'sms' => $sms,
            'raw' => $data,
        ];
    }

    public function cancelOrder(int|string $orderId): array
    {
        $data = $this->get('/user/cancel/' . rawurlencode((string) $orderId));

        return [
            'id' => (string) ($data['id'] ?? $orderId),
            'status' => (string) ($data['status'] ?? 'CANCELLED'),
            'raw' => $data,
        ];
    }

    public function finishOrder(int|string $orderId): array
    {
        $data = $this->get('/user/finish/' . rawurlencode((string) $orderId));

        return [
            'id' => (string) ($data['id'] ?? $orderId),
            'status' => (string) ($data['status'] ?? 'FINISHED'),
            'raw' => $data,
        ];
    }

    private function flattenPrices(array $prices, array $countries): array
    {
        $catalog = [];

        foreach ($prices as $country => $products) {
            if (!is_array($products)) {
                continue;
            }

            foreach ($products as $product => $operators) {
                if (!is_array($operators)) {
                    continue;
                }

                foreach ($operators as $operator => $info) {
                    if (!is_array($info)) {
                        continue;
                    }

                    $count = (int) ($info['count'] ?? 0);
                    $cost = (float) ($info['cost'] ?? 0);
                    if ($count <= 0 || $cost <= 0) {
                        continue;
                    }

                    $code = $this->catalogCode((string) $country, (string) $operator, (string) $product);
                    $countryMeta = $countries[$country] ?? [];

                    $catalog[$code] = [
                        'id' => $code,
                        'name' => $this->productLabel((string) $product),
                        'Category' => '5SIM Activation',
                        'Qty' => $count,
                        'Price' => $cost,
                        'country' => (string) $country,
                        'country_name' => (string) ($countryMeta['text_en'] ?? $country),
                        'country_iso' => $this->firstMapKey($countryMeta['iso'] ?? ''),
                        'country_prefix' => $this->firstMapKey($countryMeta['prefix'] ?? ''),
                        'operator' => (string) $operator,
                        'product' => (string) $product,
                        'rate' => (float) ($info['rate'] ?? 0),
                    ];
                }
            }
        }

        return $catalog;
    }

    private function normalizeProductPrices(array $data, string $product): array
    {
        $out = [];

        foreach ($data as $country => $value) {
            if (!is_array($value)) {
                continue;
            }

            $operators = isset($value[$product]) && is_array($value[$product]) ? $value[$product] : $value;
            foreach ($operators as $operator => $info) {
                if (!is_array($info)) {
                    continue;
                }

                $out[(string) $country][(string) $operator] = [
                    'count' => (int) ($info['count'] ?? 0),
                    'cost' => (float) ($info['cost'] ?? 0),
                    'rate' => (float) ($info['rate'] ?? 0),
                ];
            }
        }

        return $out;
    }

    private function catalogCode(string $country, string $operator, string $product): string
    {
        return '5sim:' . $country . ':' . $operator . ':' . $product;
    }

    private function decodeProduct(string $service): string
    {
        if (!str_starts_with($service, '5sim:')) {
            return $service;
        }

        $parts = explode(':', $service, 4);
        return $parts[3] ?? $service;
    }

    private function productLabel(string $product): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $product));
    }

    private function firstMapKey(mixed $value): string
    {
        if (is_array($value)) {
            return (string) (array_key_first($value) ?? '');
        }

        return (string) $value;
    }

    public function flushCache(): void
    {
        self::bustCache();
    }
}
