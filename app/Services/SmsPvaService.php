<?php

namespace App\Services;

use App\Contracts\NumberProviderContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMSPVA Activation V2 API client.
 *
 * V2 uses https://api.smspva.com and authenticates with the "apikey" HTTP
 * header. This adapter intentionally does not use 5SIM bearer auth,
 * PVAPINS auth, or deprecated SMSPVA query auth.
 */
class SmsPvaService implements NumberProviderContract
{
    private string $baseUrl;
    private string $apiKey;

    private const SERVICE_MAP = [
        'google' => 'opt1',
        'gmail' => 'opt1',
        'youtube' => 'opt1',
        'facebook' => 'opt2',
        'linkedin' => 'opt8',
        'tinder' => 'opt9',
        'viber' => 'opt11',
        'microsoft' => 'opt15',
        'skype' => 'opt15',
        'instagram' => 'opt16',
        'whatsapp' => 'opt20',
        'telegram' => 'opt29',
        'line' => 'opt37',
        'twitter' => 'opt41',
        'amazon' => 'opt44',
        'discord' => 'opt45',
        'airbnb' => 'opt46',
        'shopee' => 'opt48',
        'protonmail' => 'opt57',
        'steam' => 'opt58',
        'lazada' => 'opt60',
        'alibaba' => 'opt61',
        'yahoo' => 'opt65',
        'wechat' => 'opt67',
        'vk' => 'opt69',
        'kakao' => 'opt71',
        'uber' => 'opt72',
        'paypal' => 'opt83',
        'ebay' => 'opt83',
        'snapchat' => 'opt90',
        'wise' => 'opt91',
        'netflix' => 'opt101',
        'tiktok' => 'opt104',
        'coinbase' => 'opt112',
        'signal' => 'opt127',
        'apple' => 'opt131',
        'openai' => 'opt132',
        'chatgpt' => 'opt132',
        'revolut' => 'opt133',
        'googlevoice' => 'opt140',
        'twitch' => 'opt205',
        'okx' => 'opt228',
    ];

    private const COUNTRY_DISPLAY = [
        'UK' => 'United Kingdom', 'US' => 'United States', 'FR' => 'France',
        'DE' => 'Germany', 'ES' => 'Spain', 'IT' => 'Italy',
        'AU' => 'Australia', 'MX' => 'Mexico', 'BR' => 'Brazil',
        'PH' => 'Philippines', 'ID' => 'Indonesia', 'JP' => 'Japan',
        'RO' => 'Romania', 'PT' => 'Portugal', 'CA' => 'Canada',
        'AR' => 'Argentina', 'PL' => 'Poland', 'GR' => 'Greece',
        'AL' => 'Albania', 'AT' => 'Austria', 'BD' => 'Bangladesh',
        'BE' => 'Belgium', 'BO' => 'Bolivia', 'BA' => 'Bosnia',
        'BG' => 'Bulgaria', 'KH' => 'Cambodia', 'CM' => 'Cameroon',
        'CL' => 'Chile', 'CO' => 'Colombia', 'CR' => 'Costa Rica',
        'HR' => 'Croatia', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic',
        'DK' => 'Denmark', 'DO' => 'Dominican Rep.', 'EE' => 'Estonia',
        'FI' => 'Finland', 'GE' => 'Georgia', 'GI' => 'Gibraltar',
        'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IE' => 'Ireland',
        'IL' => 'Israel', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya',
        'KG' => 'Kyrgyzstan', 'LV' => 'Latvia', 'LT' => 'Lithuania',
        'MK' => 'N. Macedonia', 'MY' => 'Malaysia', 'MT' => 'Malta',
        'MD' => 'Moldova', 'MA' => 'Morocco', 'NL' => 'Netherlands',
        'NZ' => 'New Zealand', 'PK' => 'Pakistan', 'PY' => 'Paraguay',
        'RS' => 'Serbia', 'SG' => 'Singapore', 'SK' => 'Slovakia',
        'SI' => 'Slovenia', 'ZA' => 'South Africa', 'SE' => 'Sweden',
        'CH' => 'Switzerland', 'TZ' => 'Tanzania', 'TH' => 'Thailand',
        'TR' => 'Turkey', 'UA' => 'Ukraine', 'VN' => 'Vietnam',
    ];

    public function __construct(string $apiKey, string $baseUrl = 'https://api.smspva.com/')
    {
        $this->apiKey = trim($apiKey);
        $baseUrl = rtrim(trim($baseUrl) ?: 'https://api.smspva.com', '/');

        if (preg_match('#^https?://(www\.)?smspva\.com/?$#i', $baseUrl)) {
            $baseUrl = 'https://api.smspva.com';
        }

        $this->baseUrl = $baseUrl;
    }

    public function getBalance(): float
    {
        $data = $this->v2('GET', '/activation/balance');

        return (float) ($data['data']['balance'] ?? 0);
    }

    public function testConnection(): bool
    {
        try {
            $this->getBalance();
            return true;
        } catch (\Throwable $e) {
            Log::warning('SMSPVA testConnection failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllProducts(): array
    {
        return Cache::remember('smspva_products_v2_' . $this->cacheSuffix(), 900, function () {
            $data = $this->v2('GET', '/activation/serviceprice/UK');
            $items = $data['data'] ?? [];

            if (!is_array($items)) {
                Log::warning('SMSPVA getAllProducts unexpected V2 catalog format', ['data_type' => gettype($items)]);
                return [];
            }

            $out = [];
            foreach ($items as $item) {
                $item = is_array($item) ? $item : (array) $item;
                $code = (string) ($item['s'] ?? '');
                if ($code === '') {
                    continue;
                }

                $operatorPrices = (array) ($item['po'] ?? []);
                $out[$code] = [
                    'Category' => 'SMS Activation',
                    'Qty' => max(1, count($operatorPrices)),
                    'Price' => (float) ($item['p'] ?? 0),
                    'name' => (string) ($item['sd'] ?? $code),
                    'id' => $code,
                ];
            }

            Log::info('SMSPVA getAllProducts V2 parsed catalog', ['products' => count($out)]);

            return $out;
        });
    }

    public function getCountries(): array
    {
        $out = [];
        foreach (self::COUNTRY_DISPLAY as $code => $name) {
            $out[$code] = ['text_en' => $name, 'iso' => $code];
        }

        return $out;
    }

    public function getPrices(string $product): array
    {
        $service = $this->toOptCode($product);
        $cacheKey = "smspva_prices_v2_{$service}_" . $this->cacheSuffix();
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $data = $this->v2('GET', "/activation/serviceprices/{$service}");
        $countries = $data['data']['clist'] ?? [];
        $result = [];

        if (is_array($countries)) {
            foreach ($countries as $country) {
                $country = is_array($country) ? $country : (array) $country;
                $cc = (string) ($country['ccode'] ?? '');
                $operators = $country['opers'] ?? [];

                if ($cc === '' || !is_array($operators)) {
                    continue;
                }

                foreach ($operators as $operator) {
                    $operator = is_array($operator) ? $operator : (array) $operator;
                    $count = (int) ($operator['count'] ?? 0);
                    $price = (float) ($operator['price'] ?? 0);

                    if ($count <= 0 || $price <= 0) {
                        continue;
                    }

                    $opCode = (string) ($operator['opcode'] ?? 'any');
                    $result[$cc][$opCode] = ['cost' => $price, 'count' => $count, 'rate' => 0.0];
                }
            }
        }

        Cache::put($cacheKey, $result, 600);

        return $result;
    }

    public function getAllProductsByCountry(string $country): array
    {
        $cc = $this->toSmsPvaCode($country);
        $data = $this->v2('GET', "/activation/serviceprice/{$cc}");
        $items = $data['data'] ?? [];
        $out = [];

        if (!is_array($items)) {
            return [];
        }

        foreach ($items as $item) {
            $item = is_array($item) ? $item : (array) $item;
            $code = (string) ($item['s'] ?? '');
            if ($code === '') {
                continue;
            }

            $operatorPrices = (array) ($item['po'] ?? []);
            $out[$code] = [
                'Category' => 'SMS Activation',
                'Qty' => max(1, count($operatorPrices)),
                'Price' => (float) ($item['p'] ?? 0),
                'name' => (string) ($item['sd'] ?? $code),
                'id' => $code,
            ];
        }

        return $out;
    }

    public function getProductsForCountries(array $countries): array
    {
        $out = [];
        foreach ($countries as $country) {
            try {
                $out[$country] = $this->getAllProductsByCountry((string) $country);
            } catch (\Throwable $e) {
                Log::warning('SMSPVA getProductsForCountries failed', [
                    'country' => $country,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $out;
    }

    public function getProductsByCountry(string $country, string $service): array
    {
        $cc = $this->toSmsPvaCode($country);
        $prices = $this->getPrices($service);
        $operators = $prices[$cc] ?? [];
        $best = null;

        foreach ($operators as $operator => $info) {
            $count = (int) ($info['count'] ?? 0);
            $cost = (float) ($info['cost'] ?? 0);
            if ($count <= 0 || $cost <= 0) {
                continue;
            }
            if ($best === null || $cost < (float) $best['Cost']) {
                $best = ['Cost' => $cost, 'Price' => $cost, 'count' => $count, 'Qty' => $count, 'operator' => (string) $operator];
            }
        }

        return $best ? ['any' => $best] : [];
    }

    public function buyNumber(string $country, string $operator, string $service): array
    {
        $cc = $this->toSmsPvaCode($country);
        $optCode = $this->toOptCode($service);
        $query = [];

        if ($operator !== '' && $operator !== 'any') {
            $query['operator'] = $operator;
        }

        $data = $this->v2('GET', "/activation/number/{$cc}/{$optCode}", $query);
        $payload = $data['data'] ?? [];
        $payload = is_array($payload) ? $payload : [];
        $id = (string) ($payload['orderId'] ?? '');
        $phone = (string) ($payload['phoneNumber'] ?? '');

        if ($id === '' || $phone === '') {
            Log::warning('SMSPVA buyNumber missing required fields', ['fields' => array_keys($payload)]);
            throw new \RuntimeException('SMSPVA buyNumber response did not include order ID or phone number.');
        }

        return [
            'id' => $id,
            'phone' => $phone,
            'operator' => $operator !== '' ? $operator : 'any',
            'status' => 'PENDING',
            'expires' => now()->addSeconds((int) ($payload['orderExpireIn'] ?? 600))->toIso8601String(),
            'sms' => [],
            'raw' => ['country' => $cc, 'service' => $optCode, 'countryCode' => $payload['countryCode'] ?? null],
        ];
    }

    public function checkOrder(int|string $orderId): array
    {
        $data = $this->v2('GET', "/activation/sms/{$orderId}");
        $payload = $data['data'] ?? [];
        $payload = is_array($payload) ? $payload : [];
        $sms = $payload['sms'] ?? [];
        $sms = is_array($sms) ? $sms : [];
        $status = (string) ($payload['status'] ?? ($sms ? 'SMS_READY' : 'PENDING_SMS'));
        $fullText = (string) ($sms['fullText'] ?? $payload['fullText'] ?? '');
        $code = (string) ($sms['code'] ?? $payload['code'] ?? '');
        $smsArr = [];

        if ($fullText !== '' || $code !== '') {
            $smsArr[] = [
                'text' => $fullText !== '' ? $fullText : $code,
                'created_at' => now()->toIso8601String(),
                'sender' => 'SMSPVA',
            ];
        }

        return ['status' => $this->mapSmsStatus($status), 'sms' => $smsArr, 'raw' => $payload];
    }

    public function cancelOrder(int|string $orderId): array
    {
        try {
            $raw = $this->v2('PUT', "/activation/cancelorder/{$orderId}");
            return array_merge($raw, ['status' => 'CANCELLED']);
        } catch (\Throwable $e) {
            Log::warning("SMSPVA cancelOrder {$orderId} failed: " . $e->getMessage());
            return ['status' => 'CANCELLED'];
        }
    }

    public function finishOrder(int|string $orderId): array
    {
        try {
            $raw = $this->v2('PUT', "/activation/stopsms/{$orderId}");
            return array_merge($raw, ['status' => 'FINISHED']);
        } catch (\Throwable $e) {
            Log::warning("SMSPVA finishOrder {$orderId} failed: " . $e->getMessage());
            return ['status' => 'FINISHED'];
        }
    }

    public function banNumber(int|string $orderId): array
    {
        try {
            $raw = $this->v2('PUT', "/activation/blocknumber/{$orderId}");
            return array_merge($raw, ['status' => 'BANNED']);
        } catch (\Throwable $e) {
            Log::warning("SMSPVA banNumber {$orderId} failed: " . $e->getMessage());
            return ['status' => 'BANNED'];
        }
    }

    public function mapSmsStatus(string $raw): string
    {
        return match (strtoupper(trim($raw))) {
            '1', '4', 'RECEIVED', 'SMS_RECEIVED', 'SMS_READY' => 'RECEIVED',
            '2', 'WAITING', 'PENDING', 'PENDING_SMS', 'PENDING_PAYMENT' => 'PENDING',
            '3', '8', 'CANCELLED', 'CANCELED' => 'CANCELLED',
            '5', '6', 'FINISHED', 'DONE' => 'FINISHED',
            '-2', 'BANNED', 'BAD' => 'BANNED',
            '7', 'EXPIRED', 'TIMEOUT' => 'EXPIRED',
            default => 'PENDING',
        };
    }

    public function flushCache(): void
    {
        self::bustCache();
    }

    public static function bustCache(): void
    {
        Cache::increment('nexahub_sms_v');
    }

    private function v2(string $method, string $path, array $query = []): array
    {
        if ($this->apiKey === '' || $this->isPlaceholderKey()) {
            throw new \RuntimeException('SMSPVA API key is missing or invalid.');
        }

        try {
            $request = Http::timeout(15)
                ->connectTimeout(5)
                ->acceptJson()
                ->withHeaders(['apikey' => $this->apiKey]);

            $response = strtoupper($method) === 'PUT'
                ? $request->put($this->baseUrl . $path, $query)
                : $request->get($this->baseUrl . $path, $query);

            $body = trim($response->body());
            $data = $response->json();
            $safePreview = mb_substr($this->redactSecrets($body), 0, 500);

            if (!is_array($data)) {
                Log::warning('SMSPVA V2 returned non-JSON response', [
                    'method' => $method,
                    'path' => $path,
                    'http_status' => $response->status(),
                    'preview' => $safePreview,
                ]);
                throw new \RuntimeException('SMSPVA returned an unexpected response format.');
            }

            $statusCode = (int) ($data['statusCode'] ?? $response->status());
            if (!$response->ok() || $statusCode < 200 || $statusCode >= 300 || isset($data['error'])) {
                Log::warning('SMSPVA V2 API error', [
                    'method' => $method,
                    'path' => $path,
                    'http_status' => $response->status(),
                    'status_code' => $statusCode,
                    'error_type' => $data['error']['type'] ?? null,
                    'error_description' => $data['error']['description'] ?? null,
                    'preview' => $safePreview,
                ]);
                throw new \RuntimeException($this->v2ErrorMessage($data, $response->status()));
            }

            return $data;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('SMSPVA provider unreachable: ' . $e->getMessage());
        }
    }

    private function isPlaceholderKey(): bool
    {
        return strlen($this->apiKey) < 12
            || str_contains(strtoupper($this->apiKey), 'REPLACE')
            || str_contains(strtoupper($this->apiKey), 'YOUR_KEY')
            || str_contains(strtoupper($this->apiKey), 'PLACEHOLDER');
    }

    private function toOptCode(string $service): string
    {
        $service = strtolower(trim($service));

        return self::SERVICE_MAP[$service] ?? $service;
    }

    private function toSmsPvaCode(string $country): string
    {
        if ($country === 'any' || $country === '') {
            return 'UK';
        }

        $upper = strtoupper($country);

        return isset(self::COUNTRY_DISPLAY[$upper]) ? $upper : 'UK';
    }

    private function cacheSuffix(): string
    {
        return Cache::get('nexahub_sms_v', 0) . '_' . substr(hash('sha256', $this->apiKey), 0, 12);
    }

    private function redactSecrets(string $value): string
    {
        if ($this->apiKey !== '') {
            $value = str_replace($this->apiKey, '[redacted-api-key]', $value);
        }

        return preg_replace('/("apikey"\s*:\s*")[^"]+/i', '$1[redacted-api-key]', $value) ?? $value;
    }

    private function v2ErrorMessage(array $data, int $httpStatus): string
    {
        $type = strtoupper((string) ($data['error']['type'] ?? ''));
        $description = (string) ($data['error']['description'] ?? '');
        $haystack = strtolower($type . ' ' . $description);

        if ($httpStatus === 400 || str_contains($haystack, 'apikey') || str_contains($haystack, 'api key') || str_contains($haystack, 'key')) {
            return 'SMSPVA invalid API key or missing apikey header.';
        }
        if ($httpStatus === 407 || str_contains($haystack, 'balance')) {
            return 'SMSPVA balance is too low for this request.';
        }
        if ($httpStatus === 411 || str_contains($haystack, 'rate') || str_contains($haystack, 'limited')) {
            return 'SMSPVA rate limit or temporary access limit reached.';
        }
        if ($httpStatus === 409 || str_contains($haystack, 'number') || str_contains($haystack, 'stock')) {
            return 'SMSPVA has no available number for the selected country/service.';
        }
        if ($httpStatus >= 500) {
            return 'SMSPVA server is busy or failed to process the request.';
        }

        return 'SMSPVA API error: ' . ($description ?: ($type ?: 'Unexpected provider response.'));
    }
}
