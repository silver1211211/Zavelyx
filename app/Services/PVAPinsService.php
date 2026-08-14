<?php

namespace App\Services;

use App\Contracts\NumberProviderContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PVAPinsService implements NumberProviderContract
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout = 15;
    private int $connectTimeout = 6;
    private int $catalogBatchSize = 20;

    public function __construct(string $apiKey, string $baseUrl = 'https://api.pvapins.com/user/api/')
    {
        $this->apiKey = trim($apiKey);
        $baseUrl = trim($baseUrl);

        if ($baseUrl === '' || preg_match('#^https?://(www\.)?pvapins\.com/?$#i', $baseUrl)) {
            $baseUrl = 'https://api.pvapins.com/user/api/';
        }

        $this->baseUrl = rtrim($baseUrl, '/') . '/';
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
        return $this->baseUrl . ltrim($path, '/');
    }

    private function catalogCode(array $country, array $app): string
    {
        $countryId = (string) ($country['id'] ?? $this->countryCode($country));
        $appId = (string) ($app['id'] ?? '');
        $appName = $this->appName($app);
        $appPart = $appId !== '' ? $appId : (strtolower(preg_replace('/[^a-z0-9]+/i', '-', $appName)) ?: md5($appName));

        return 'pvapins:' . $countryId . ':' . $appPart;
    }

    private function get(string $path, array $params = [], bool $auth = false): array
    {
        if ($auth) {
            $params['customer'] = $this->apiKey;
        }

        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->get($this->endpoint($path), $params);

            $body = trim($response->body());

            Log::debug('PVAPins HTTP response', [
                'endpoint' => $path,
                'params' => $this->safeParams($params),
                'status' => $response->status(),
                'preview' => mb_substr($body, 0, 500),
            ]);

            if ($response->status() === 429) {
                throw new \RuntimeException('PVAPins rate limit exceeded.');
            }

            if ($response->failed()) {
                throw new \RuntimeException("PVAPins HTTP {$response->status()} from {$path}.");
            }

            $json = $response->json();
            if ($json === null) {
                $this->throwTextError($body, $path);
                return ['_text' => $body];
            }

            $data = is_array($json) ? $json : ['_raw' => $json];
            $this->throwJsonError($data, $path);

            return $data;
        } catch (ConnectionException $e) {
            throw new \RuntimeException('PVAPins provider unreachable: ' . $e->getMessage());
        }
    }

    private function safeParams(array $params): array
    {
        foreach (['customer', 'api_key', 'apikey', 'key'] as $key) {
            if (array_key_exists($key, $params)) {
                $params[$key] = '***';
            }
        }

        return $params;
    }

    private function throwTextError(string $body, string $path): void
    {
        $lower = strtolower($body);

        if (str_contains($lower, 'customer not found') || str_contains($lower, 'invalid') && str_contains($lower, 'key')) {
            throw new \RuntimeException('PVAPins invalid API key.');
        }

        if (str_contains($lower, 'not possible') || str_contains($lower, 'limit')) {
            throw new \RuntimeException('PVAPins rate limit or request limit reached.');
        }

        if (str_contains($lower, 'not enough balance') || str_contains($lower, 'insufficient balance')) {
            throw new \RuntimeException('PVAPins account balance is too low for this request.');
        }

        if (str_contains($lower, 'error 102')) {
            throw new \RuntimeException('PVAPins temporary provider error. Try again later.');
        }

        if ($body !== '' && !is_numeric($body)) {
            Log::warning('PVAPins unexpected text response', [
                'endpoint' => $path,
                'preview' => mb_substr($body, 0, 500),
            ]);
        }
    }

    private function throwJsonError(array $data, string $path): void
    {
        $message = (string) ($data['error'] ?? $data['message'] ?? $data['data'] ?? '');
        $code = (int) ($data['code'] ?? 0);
        $lower = strtolower($message);

        if ($code === 200 && $message !== '') {
            throw new \RuntimeException('PVAPins API error: ' . $message);
        }

        if (str_contains($lower, 'customer not found') || str_contains($lower, 'invalid key')) {
            throw new \RuntimeException('PVAPins invalid API key.');
        }

        if (str_contains($lower, 'not enough balance') || str_contains($lower, 'insufficient balance')) {
            throw new \RuntimeException('PVAPins account balance is too low for this request.');
        }

        if (isset($data['status']) && in_array(strtolower((string) $data['status']), ['error', 'failed', 'fail'], true)) {
            throw new \RuntimeException('PVAPins API error: ' . ($message ?: "unexpected response from {$path}."));
        }
    }

    public function getBalance(): float
    {
        $data = $this->get('get_balance.php', [], true);

        if (isset($data['_raw']) && is_numeric($data['_raw'])) {
            return (float) $data['_raw'];
        }

        if (isset($data['_text']) && is_numeric($data['_text'])) {
            return (float) $data['_text'];
        }

        if (isset($data['data']) && is_numeric($data['data'])) {
            return (float) $data['data'];
        }

        return (float) ($data['balance'] ?? $data['data']['balance'] ?? $data['amount'] ?? 0);
    }

    public function testConnection(): bool
    {
        try {
            $this->getBalance();
            return true;
        } catch (\Throwable $e) {
            Log::warning('PVAPins testConnection failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getCountries(): array
    {
        return Cache::remember('pvapins_countries_' . $this->cv(), 3600, function () {
            $countries = $this->loadCountries();
            $out = [];

            foreach ($countries as $country) {
                $name = $this->countryName($country);
                if ($name === '') {
                    continue;
                }

                $code = $this->countryCode($country);
                $out[$code] = [
                    'text_en' => $name,
                    'iso' => strtoupper((string) ($country['iso'] ?? $country['iso2'] ?? '')),
                    'prefix' => (string) ($country['prefix'] ?? $country['phone_code'] ?? ''),
                    'provider_id' => $country['id'] ?? null,
                ];
            }

            return $out;
        });
    }

    public function getAllProducts(): array
    {
        $cacheKey = 'pvapins_products_' . $this->cv();
        $cached = Cache::get($cacheKey);
        if ($cached !== null && $cached !== []) {
            return $cached;
        }

        $catalog = $this->buildCatalog();

        if ($catalog === []) {
            Log::warning('PVAPins catalog parser returned 0 products after raw country/app responses were checked.');
            return [];
        }

        if (count($catalog) <= 5000) {
            Cache::put($cacheKey, $catalog, 900);
        }

        return $catalog;
    }

    public function getPrices(string $product): array
    {
        $cacheKey = 'pvapins_prices_' . md5($product) . '_' . $this->cv();
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $countries = $this->loadCountries();
        $result = [];
        $requestedApp = $this->decodeService($product);

        foreach ($countries as $country) {
            $countryName = $this->countryName($country);
            $countryCode = $this->countryCode($country);
            $countryId = $country['id'] ?? null;

            if (!$countryId || $countryName === '') {
                continue;
            }

            foreach ($this->loadApps((string) $countryId) as $app) {
                $appName = $this->appName($app);
                if (!$this->sameProduct($requestedApp, $appName, $app) && !$this->sameProduct($product, $appName, $app)) {
                    continue;
                }

                $cost = (float) ($app['deduct'] ?? $app['price'] ?? $app['cost'] ?? 0);
                $result[$countryCode]['any'] = [
                    'count' => (int) ($app['qty'] ?? $app['count'] ?? $app['stock'] ?? 1),
                    'cost' => $cost,
                    'rate' => 0.0,
                ];
            }
        }

        if ($result !== []) {
            Cache::put($cacheKey, $result, 600);
        }

        return $result;
    }

    public function getAllProductsByCountry(string $country): array
    {
        return Cache::remember('pvapins_country_' . md5($country) . '_' . $this->cv(), 300, function () use ($country) {
            $match = $this->findCountry($country);
            if (!$match || empty($match['id'])) {
                return [];
            }

            $out = [];
            foreach ($this->loadApps((string) $match['id']) as $app) {
                $name = $this->appName($app);
                if ($name === '') {
                    continue;
                }

                $out[$name] = [
                    'Category' => !empty($app['trending']) ? 'Trending' : 'Activation',
                    'Qty' => (int) ($app['qty'] ?? $app['count'] ?? $app['stock'] ?? 1),
                    'Price' => (float) ($app['deduct'] ?? $app['price'] ?? $app['cost'] ?? 0),
                ];
            }

            return $out;
        });
    }

    public function getProductsForCountries(array $countries): array
    {
        $result = [];
        foreach ($countries as $country) {
            $result[$country] = $this->getAllProductsByCountry((string) $country);
        }

        return $result;
    }

    public function buyNumber(string $country, string $operator, string $service): array
    {
        $countryName = $this->providerCountryName($country);
        $appName = $this->decodeService($service);
        $params = [
            'app' => $appName,
            'country' => $countryName,
        ];

        if ($operator !== '' && $operator !== 'any') {
            $params['operator'] = $operator;
        }

        $data = $this->get('get_number.php', $params, true);

        $phone = $this->numberFromResponse($data);
        $id = (string) ($data['id'] ?? $data['activation_id'] ?? $data['order_id'] ?? '');
        if ($id === '') {
            $id = implode('|', [$phone, $countryName, $appName]);
        }

        if ($phone === '') {
            throw new \RuntimeException('PVAPins returned an unexpected get_number response.');
        }

        return [
            'id' => $id,
            'phone' => $phone,
            'operator' => $operator ?: 'any',
            'status' => 'PENDING',
            'expires' => now()->addMinutes(15)->toIso8601String(),
            'sms' => [],
        ];
    }

    public function checkOrder(int|string $orderId): array
    {
        $parts = explode('|', (string) $orderId);
        $number = $parts[0] ?? (string) $orderId;
        $country = $parts[1] ?? '';
        $app = $parts[2] ?? '';

        if ($country === '' || $app === '') {
            return ['id' => (string) $orderId, 'phone' => $number, 'status' => 'PENDING', 'sms' => []];
        }

        $data = $this->get('get_sms.php', [
            'number' => $number,
            'country' => $country,
            'app' => $app,
        ], true);

        $text = (string) ($data['sms'] ?? $data['message'] ?? $data['text'] ?? $data['_text'] ?? '');
        if ($text === '' || str_contains(strtolower($text), 'not received')) {
            return ['id' => (string) $orderId, 'phone' => $number, 'status' => 'PENDING', 'sms' => []];
        }

        return [
            'id' => (string) $orderId,
            'phone' => $number,
            'status' => 'RECEIVED',
            'sms' => [[
                'text' => $text,
                'sender' => 'PVAPins',
                'created_at' => now()->toIso8601String(),
            ]],
        ];
    }

    public function cancelOrder(int|string $orderId): array
    {
        $parts = explode('|', (string) $orderId);
        if (count($parts) >= 3) {
            $this->get('get_reject_number.php', [
                'number' => $parts[0],
                'country' => $parts[1],
                'app' => $parts[2],
            ], true);
        }

        return ['id' => (string) $orderId, 'status' => 'CANCELLED'];
    }

    public function finishOrder(int|string $orderId): array
    {
        return ['id' => (string) $orderId, 'status' => 'FINISHED'];
    }

    private function buildCatalog(): array
    {
        $countries = $this->loadCountries();
        $catalog = [];

        foreach ($this->loadAppsForCountries($countries) as $countryApps) {
            $country = $countryApps['country'];
            $countryId = $country['id'] ?? null;
            $countryName = $this->countryName($country);
            if (!$countryId) {
                continue;
            }

            foreach ($countryApps['apps'] as $app) {
                $name = $this->appName($app);
                if ($name === '') {
                    continue;
                }

                $price = (float) ($app['deduct'] ?? $app['price'] ?? $app['cost'] ?? 0);
                $qty = (int) ($app['qty'] ?? $app['count'] ?? $app['stock'] ?? 1);
                $code = $this->catalogCode($country, $app);

                $catalog[$code] = [
                    'id' => (string) ($app['id'] ?? $code),
                    'name' => $name,
                    'full_name' => $name,
                    'Category' => !empty($app['trending']) ? 'Trending' : 'Activation',
                    'Qty' => max(0, $qty),
                    'Price' => $price,
                    'country' => $this->countryCode($country),
                    'country_id' => (string) $countryId,
                    'country_name' => $countryName,
                    'app_id' => (string) ($app['id'] ?? ''),
                    'app_name' => $name,
                    'deduct' => $price,
                    'trending' => (int) ($app['trending'] ?? 0),
                    'timestamp' => $app['timestamp'] ?? null,
                ];
            }
        }

        return $catalog;
    }

    private function loadAppsForCountries(array $countries): array
    {
        $result = [];

        foreach (array_chunk($countries, $this->catalogBatchSize) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                $requests = [];

                foreach ($chunk as $country) {
                    $countryId = (string) ($country['id'] ?? '');
                    if ($countryId === '') {
                        continue;
                    }

                    $requests[$countryId] = $pool
                        ->as($countryId)
                        ->acceptJson()
                        ->timeout($this->timeout)
                        ->connectTimeout($this->connectTimeout)
                        ->get($this->endpoint('load_apps.php'), ['country_id' => $countryId]);
                }

                return $requests;
            });

            foreach ($chunk as $country) {
                $countryId = (string) ($country['id'] ?? '');
                if ($countryId === '') {
                    continue;
                }

                $response = $responses[$countryId] ?? null;
                if (!$response) {
                    continue;
                }

                if ($response instanceof \Throwable) {
                    Log::warning('PVAPins load_apps connection failed during catalog sync', [
                        'country_id' => $countryId,
                        'error' => $response->getMessage(),
                    ]);
                    continue;
                }

                $body = trim($response->body());
                Log::debug('PVAPins HTTP response', [
                    'endpoint' => 'load_apps.php',
                    'params' => ['country_id' => $countryId],
                    'status' => $response->status(),
                    'preview' => mb_substr($body, 0, 500),
                ]);

                if ($response->failed()) {
                    Log::warning('PVAPins load_apps failed during catalog sync', [
                        'country_id' => $countryId,
                        'status' => $response->status(),
                        'preview' => mb_substr($body, 0, 500),
                    ]);
                    continue;
                }

                $json = $response->json();
                if ($json === null) {
                    Log::warning('PVAPins load_apps returned non-JSON during catalog sync', [
                        'country_id' => $countryId,
                        'preview' => mb_substr($body, 0, 500),
                    ]);
                    continue;
                }

                $result[] = [
                    'country' => $country,
                    'apps' => $this->listFromResponse(is_array($json) ? $json : ['_raw' => $json], 'apps'),
                ];
            }
        }

        return $result;
    }

    private function loadCountries(): array
    {
        $data = $this->get('load_countries.php');
        return $this->listFromResponse($data, 'countries');
    }

    private function loadApps(string $countryId): array
    {
        $data = $this->get('load_apps.php', ['country_id' => $countryId]);
        return $this->listFromResponse($data, 'apps');
    }

    private function listFromResponse(array $data, string $key): array
    {
        if (isset($data[$key]) && is_array($data[$key])) {
            $data = $data[$key];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        if (!array_is_list($data)) {
            $data = array_values($data);
        }

        return array_values(array_filter($data, fn ($row) => is_array($row)));
    }

    private function countryName(array $country): string
    {
        return trim((string) ($country['full_name'] ?? $country['name'] ?? $country['country'] ?? ''));
    }

    private function countryCode(array $country): string
    {
        $name = $this->countryName($country);
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $name)) ?: strtolower((string) ($country['id'] ?? ''));
    }

    private function appName(array $app): string
    {
        return trim((string) ($app['full_name'] ?? $app['name'] ?? $app['app'] ?? $app['code'] ?? ''));
    }

    private function sameProduct(string $product, string $appName, array $app): bool
    {
        $candidates = [
            $appName,
            (string) ($app['id'] ?? ''),
            (string) ($app['code'] ?? ''),
            (string) ($app['app_name'] ?? ''),
            (string) ($app['full_name'] ?? ''),
            strtolower(preg_replace('/[^a-z0-9]+/i', '', $appName)),
        ];

        $needle = strtolower(trim($product));
        foreach ($candidates as $candidate) {
            if (strtolower(trim($candidate)) === $needle) {
                return true;
            }
        }

        return false;
    }

    private function decodeService(string $service): string
    {
        if (!str_starts_with($service, 'pvapins:')) {
            return $service;
        }

        foreach ($this->getAllProducts() as $code => $info) {
            if ((string) $code === $service && is_array($info)) {
                $name = trim((string) ($info['app_name'] ?? $info['name'] ?? $info['full_name'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return $service;
    }

    private function numberFromResponse(array $data): string
    {
        $value = $data['number'] ?? $data['phone'] ?? $data['msisdn'] ?? $data['data'] ?? $data['_text'] ?? '';

        if (is_array($value)) {
            $value = $value['number'] ?? $value['phone'] ?? $value['msisdn'] ?? '';
        }

        return trim((string) $value);
    }

    private function findCountry(string $country): ?array
    {
        $needle = strtolower(preg_replace('/[^a-z0-9]+/i', '', $country));

        foreach ($this->loadCountries() as $item) {
            if ($this->countryCode($item) === $needle || strtolower($this->countryName($item)) === strtolower($country)) {
                return $item;
            }
        }

        return null;
    }

    private function providerCountryName(string $country): string
    {
        $match = $this->findCountry($country);
        return $match ? $this->countryName($match) : $country;
    }
}
