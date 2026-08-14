<?php

namespace App\Http\Controllers;

use App\Models\NumberOrder;
use App\Models\Service;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\NumberProviderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SmsController extends Controller
{
    public function __construct(private NumberProviderService $providerService) {}

    // â”€â”€ Pages â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function buy(): Response
    {
        return Inertia::render('Sms/Buy');
    }

    public function numbers(Request $request): Response
    {
        $orders = NumberOrder::with(['smsMessages' => fn ($q) => $q->orderBy('received_at')])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (NumberOrder $o) => $this->formatOrder($o));

        return Inertia::render('Sms/Numbers', ['orders' => $orders]);
    }

    // â”€â”€ Popular services shown first â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private const PRIORITY_SERVICES = [
        'telegram', 'whatsapp', 'google', 'gmail', 'discord', 'tiktok',
        'instagram', 'facebook', 'snapchat', 'twitter', 'x', 'openai',
        'uber', 'binance', 'amazon', 'microsoft',
    ];

    private function sortServices(array $services): array
    {
        $priority = array_flip(self::PRIORITY_SERVICES);
        usort($services, function ($a, $b) use ($priority) {
            $pa = $priority[$a['id']] ?? PHP_INT_MAX;
            $pb = $priority[$b['id']] ?? PHP_INT_MAX;
            if ($pa !== $pb) return $pa <=> $pb;
            return $b['qty'] <=> $a['qty'];
        });
        return array_values($services);
    }

    // â”€â”€ Top countries to pre-check for stock â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    // All codes verified against 5SIM /guest/countries â€” only valid codes included.
    // Absent from 5SIM: russia, ukraine, myanmar, uk, turkey, iran, iraq, uae, japan, singapore, southkorea.
    private const TOP_COUNTRIES = [
        'indonesia', 'philippines', 'vietnam', 'india', 'brazil', 'usa',
        'cambodia', 'bangladesh', 'pakistan', 'malaysia', 'thailand', 'srilanka',
        'nigeria', 'ghana', 'kenya', 'ethiopia', 'egypt', 'morocco', 'southafrica',
        'tanzania', 'senegal', 'ivorycoast',
        'mexico', 'colombia', 'argentina', 'chile', 'peru',
        'saudiarabia', 'jordan', 'israel', 'kuwait',
        'england', 'germany', 'france', 'poland', 'romania', 'czech',
        'italy', 'spain', 'netherlands', 'sweden', 'portugal', 'bulgaria',
        'uzbekistan', 'kazakhstan', 'kyrgyzstan', 'tajikistan',
        'taiwan', 'hongkong', 'australia', 'canada',
    ];

    // â”€â”€ AJAX: Service catalog â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * GET /sms/services
     * Returns the full service catalog. Tries active providers in priority order.
     */
    public function services(): JsonResponse
    {
        try {
            $cacheVersion = Cache::get('nexahub_sms_v', 0);
            $services = Cache::remember("sms_services_local_{$cacheVersion}", 1800, function () {
                return Service::query()
                    ->where('type', 'sms')
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->limit(500)
                    ->get(['id', 'name', 'provider_service_code', 'selling_price', 'max_amount', 'metadata'])
                    ->map(function (Service $service) {
                        $metadata = is_array($service->metadata) ? $service->metadata : [];
                        $qty = (int) ($metadata['available_count'] ?? $service->max_amount ?? 0);

                        return [
                            'id'       => (string) ($service->provider_service_code ?: $service->id),
                            'label'    => $service->name,
                            'category' => (string) ($metadata['number_provider_driver'] ?? 'Activation'),
                            'qty'      => $qty,
                            'price'    => (float) $service->selling_price,
                        ];
                    })
                    ->values()
                    ->all();
            });

            return response()->json($this->sortServices($services));
        } catch (\Throwable $e) {
            Log::error('SMS services() failed: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * GET /sms/countries
     * Returns 153 country codes + English names from 5SIM, sorted alphabetically.
     * Cached via FiveSimService::getCountries() for 1 hour.
     */
    public function countries(): JsonResponse
    {
        try {
            $cacheVersion = Cache::get('nexahub_sms_v', 0);
            $countries = Cache::remember("sms_countries_local_{$cacheVersion}", 1800, fn () => $this->localCountryRows());

            return response()->json($countries);
        } catch (\Throwable $e) {
            Log::error('SMS countries() failed: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * GET /sms/products?service=telegram[&country=usa]
     *
     * Without ?country: returns [] (flat API has no per-country breakdown).
     * With ?country=usa: calls /guest/products/usa/any, extracts the service row,
     * and returns { available, country, operator, qty, cost, price }.
     *
     * The 5SIM endpoint is /{country}/{OPERATOR} â€” operator defaults to "any".
     */
    public function products(Request $request): JsonResponse
    {
        $service = trim((string) $request->query('service', ''));
        $country = trim(strtolower((string) $request->query('country', '')));

        if (!$service) {
            return response()->json(['error' => 'Service is required.'], 422);
        }

        try {
            $query = $this->localServiceQuery($service);

            if ($country && $country !== 'any') {
                $query->where('sms_country', $country);
            }

            $row = $query
                ->orderBy('selling_price')
                ->first(['id', 'selling_price', 'cost_price', 'max_amount', 'metadata', 'sms_country', 'sms_operator', 'sms_available_count']);

            if (!$row) {
                return response()->json([
                    'available' => false,
                    'country'   => $country ?: 'any',
                    'operator'  => 'any',
                    'qty'       => 0,
                    'cost'      => 0,
                    'price'     => 0,
                ]);
            }

            $metadata = is_array($row->metadata) ? $row->metadata : [];
            $qty = (int) ($row->sms_available_count ?: ($metadata['available_count'] ?? $row->max_amount ?? 0));

            return response()->json([
                'available' => $qty > 0,
                'country'   => (string) ($row->sms_country ?: ($metadata['country'] ?? ($country ?: 'any'))),
                'operator'  => (string) ($row->sms_operator ?: ($metadata['operator'] ?? 'any')),
                'qty'       => $qty,
                'cost'      => (float) ($row->cost_price ?? 0),
                'price'     => (float) $row->selling_price,
            ]);
            // No country â€” flat global response (no per-country breakdown available)
        } catch (\Throwable $e) {
            Log::error("SMS products({$service}) failed: " . $e->getMessage());
            return response()->json([
                'available' => false,
                'country'   => $country ?: 'any',
                'operator'  => 'any',
                'qty'       => 0,
                'cost'      => 0,
                'price'     => 0,
            ]);
        }
    }

    /**
     * GET /sms/country-stock?service={service}
     *
     * Tries active providers in priority order. Per-provider:
     *   Primary:  getPrices(service)             â€” all countries/operators in one call.
     *   Fallback: getProductsForCountries(batch) â€” if primary returns empty.
     *
     * Non-empty results are cached 10 min (key v3). Empty results are not cached.
     *
     * Response shape per country:
     *   { code, name, qty, cost, price, operators: [{ name, count, cost, price, rate }] }
     */
    public function countryStock(Request $request): JsonResponse
    {
        $service = trim((string) $request->query('service', ''));
        if (!$service) {
            return response()->json(['error' => 'Service is required.'], 422);
        }

        try {
            $cacheVersion = Cache::get('nexahub_sms_v', 0);
            $cacheKey = "sms_country_stock_local_{$service}_{$cacheVersion}";
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached);
            }

            $list = [];
            $rows = $this->localServiceQuery($service)
                ->orderBy('selling_price')
                ->limit(250)
                ->get(['id', 'name', 'selling_price', 'cost_price', 'max_amount', 'metadata', 'sms_country', 'sms_country_name', 'sms_operator', 'sms_available_count']);

            foreach ($rows as $row) {
                $metadata = is_array($row->metadata) ? $row->metadata : [];
                $country = trim((string) ($row->sms_country ?: ($metadata['country'] ?? 'any'))) ?: 'any';
                $qty = (int) ($row->sms_available_count ?: ($metadata['available_count'] ?? $row->max_amount ?? 0));
                if ($qty <= 0) {
                    continue;
                }

                $name = (string) ($row->sms_country_name ?: ($metadata['country_name'] ?? Str::of($country)->replace(['_', '-'], ' ')->title()));
                $cost = (float) ($row->cost_price ?? 0);
                $price = (float) $row->selling_price;

                if (!isset($list[$country])) {
                    $list[$country] = [
                        'code'      => $country,
                        'name'      => $name,
                        'qty'       => 0,
                        'cost'      => $cost,
                        'price'     => $price,
                        'operators' => [],
                    ];
                }

                $list[$country]['qty'] += $qty;
                if ($price > 0 && ($list[$country]['price'] <= 0 || $price < $list[$country]['price'])) {
                    $list[$country]['price'] = $price;
                    $list[$country]['cost'] = $cost;
                }
                $list[$country]['operators'][] = [
                    'name'  => (string) ($row->sms_operator ?: ($metadata['operator'] ?? 'any')),
                    'count' => $qty,
                    'cost'  => $cost,
                    'price' => $price,
                    'rate'  => (float) ($metadata['provider_rate'] ?? 0),
                ];
            }

            $list = array_values($list);
            usort($list, fn ($a, $b) => $b['qty'] <=> $a['qty']);
            Cache::put($cacheKey, $list, 1800);

            return response()->json($list);
        } catch (\Throwable $e) {
            Log::error('SMS countryStock() failed: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
            return response()->json([]);
        }
    }

    // â”€â”€ AJAX: Purchase â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * POST /sms/buy
     * Purchase a number, deduct from wallet inside a transaction with row-lock.
     * Tries active providers in priority order; falls back to next on API failure.
     */
    public function purchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service'  => ['required', 'string', 'max:100'],
            'country'  => ['required', 'string', 'max:100'],
            'operator' => ['required', 'string', 'max:100'],
            'cost'     => ['sometimes', 'numeric', 'min:0'],
        ]);

        $user   = $request->user();
        $wallet = $user->wallet;

        if (!$wallet || !$wallet->is_active) {
            return response()->json(['error' => 'Your account is currently frozen. Contact support.'], 403);
        }

        $providers = $this->providerService->getAllActiveProviders();
        if (empty($providers)) {
            return response()->json(['error' => 'No active number provider configured.'], 503);
        }

        $submittedCost = isset($validated['cost']) ? (float) $validated['cost'] : 0;
        $lastError     = 'Purchase failed. Please try again.';
        $order         = null;

        foreach ($providers as $provider) {
            $driver = $this->providerService->driver($provider);

            // Re-verify live price with this provider
            try {
                [$effectiveCountry, $effectiveOperator, $providerCost] = $this->resolvePrice($driver, $validated);
            } catch (\Throwable $e) {
                Log::warning("SMS cost-check failed ({$provider->name}): " . $e->getMessage());
                $lastError = $e->getMessage() ?: 'Could not verify pricing.';
                continue;
            }

            $amount = $provider->applyMarkup($providerCost);

            // Price-change guard: >10% higher than what user was shown â†’ stop immediately
            if ($submittedCost > 0 && $amount > $submittedCost * 1.10) {
                Log::warning("SMS price changed: submitted={$submittedCost} live={$amount} provider={$provider->name}");
                return response()->json([
                    'error'     => 'The price has changed. Please refresh the page to see the current price before buying.',
                    'new_price' => $amount,
                ], 422);
            }

            if ((float) $wallet->balance < $amount) {
                return response()->json(['error' => 'Insufficient balance. Please top up your wallet.'], 422);
            }

            try {
                DB::transaction(function () use (
                    $user, $wallet, $provider, $driver, $validated,
                    $effectiveCountry, $effectiveOperator, $providerCost, $amount, &$order
                ) {
                    $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

                    if ((float) $wallet->balance < $amount) {
                        throw new \RuntimeException('Insufficient balance.');
                    }

                    $balanceBefore = (float) $wallet->balance;
                    $wallet->decrement('balance', $amount);
                    $wallet->refresh();

                    Log::info("SMS purchase: user={$user->id} service={$validated['service']} country={$effectiveCountry} op={$effectiveOperator} cost={$amount} provider={$provider->name}");

                    $response = $driver->buyNumber($effectiveCountry, $effectiveOperator, $validated['service']);

                    if (empty($response['id']) || empty($response['phone'])) {
                        Log::error('buyNumber returned invalid response: ' . json_encode($response));
                        throw new \RuntimeException('Provider returned invalid response. Please try again.');
                    }

                    $order = NumberOrder::create([
                        'user_id'            => $user->id,
                        'number_provider_id' => $provider->id,
                        'wallet_id'          => $wallet->id,
                        'activation_id'      => (string) $response['id'],
                        'country'            => $effectiveCountry,
                        'operator'           => $effectiveOperator,
                        'service'            => $validated['service'],
                        'phone_number'       => $response['phone'],
                        'provider_cost'      => $providerCost,
                        'markup_percent'     => (float) $provider->markup_percent,
                        'amount'             => $amount,
                        'balance_before'     => $balanceBefore,
                        'balance_after'      => (float) $wallet->balance,
                        'status'             => $this->providerService->normalizeStatus($response['status'] ?? 'PENDING'),
                        'expires_at'         => isset($response['expires'])
                            ? Carbon::parse($response['expires'])
                            : now()->addMinutes(20),
                        'raw_response'       => $response,
                    ]);

                    Transaction::create([
                        'reference'      => Str::uuid(),
                        'user_id'        => $user->id,
                        'wallet_id'      => $wallet->id,
                        'type'           => 'debit',
                        'amount'         => $amount,
                        'balance_before' => $balanceBefore,
                        'balance_after'  => (float) $wallet->balance,
                        'status'         => 'completed',
                        'description'    => "Number #{$response['id']}: {$validated['service']} ({$response['phone']})",
                        'metadata'       => ['number_order_id' => null],
                    ]);
                });

                break; // success â€” exit provider loop
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                if (str_contains($msg, 'Insufficient')) {
                    return response()->json(['error' => 'Insufficient balance. Please top up your wallet.'], 422);
                }
                Log::warning("SMS purchase provider {$provider->name} failed: " . $msg);
                $lastError = $msg ?: 'Provider failed. Trying alternativesâ€¦';
            }
        }

        if (!$order) {
            Log::error("SMS purchase all providers failed: {$lastError}");
            return response()->json(['error' => $lastError], 422);
        }

        $order->load('smsMessages');

        return response()->json([
            'order'   => $this->formatOrder($order),
            'message' => 'Number purchased successfully!',
        ]);
    }

    // â”€â”€ AJAX: Polling & actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /** GET /sms/orders/{order}/poll */
    public function poll(Request $request, NumberOrder $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Not found.'], 404);
        }

        if ($order->isTerminal()) {
            return response()->json(['order' => $this->formatOrder($order->load('smsMessages'))]);
        }

        try {
            $driver   = $this->providerService->driver($order->provider);
            $response = $driver->checkOrder($order->activation_id);

            $newStatus = $this->providerService->normalizeStatus($response['status'] ?? $order->status);

            if (!empty($response['sms']) && is_array($response['sms'])) {
                foreach ($response['sms'] as $sms) {
                    $text = $sms['text'] ?? '';
                    SmsMessage::firstOrCreate(
                        [
                            'number_order_id' => $order->id,
                            'received_at'     => Carbon::parse($sms['created_at'] ?? now()),
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

            $updates = ['status' => $newStatus, 'raw_response' => $response];

            $latestSms = $order->smsMessages()->orderByDesc('received_at')->first();
            if ($latestSms) {
                $updates['otp_code'] = $latestSms->code;
                $updates['sms_text'] = $latestSms->message;
            }

            if (in_array($newStatus, ['FINISHED', 'CANCELLED', 'BANNED', 'EXPIRED'])) {
                $updates['completed_at'] = now();
            }

            $order->update($updates);
            $order->load('smsMessages');

        } catch (\Throwable $e) {
            Log::warning("SMS poll failed for order {$order->id}: " . $e->getMessage());
        }

        return response()->json(['order' => $this->formatOrder($order)]);
    }

    /** POST /sms/orders/{order}/cancel â€” cancels and refunds */
    public function cancel(Request $request, NumberOrder $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Not found.'], 404);
        }
        if ($order->isTerminal()) {
            return response()->json(['error' => 'Order is already completed.'], 422);
        }
        if ($order->otp_code) {
            return response()->json(['error' => 'Cannot cancel: SMS code already received. This order is permanently locked.'], 422);
        }

        try {
            $driver   = $this->providerService->driver($order->provider);
            $response = $driver->cancelOrder($order->activation_id);

            $newStatus = $this->providerService->normalizeStatus($response['status'] ?? 'CANCELLED');

            DB::transaction(function () use ($order, $newStatus, $response) {
                $order->update([
                    'status'       => $newStatus,
                    'completed_at' => now(),
                    'raw_response' => $response,
                ]);

                $wallet        = Wallet::lockForUpdate()->findOrFail($order->wallet_id);
                $balanceBefore = (float) $wallet->balance;
                $wallet->increment('balance', $order->amount);
                $wallet->refresh();

                Transaction::create([
                    'reference'      => Str::uuid(),
                    'user_id'        => $order->user_id,
                    'wallet_id'      => $wallet->id,
                    'type'           => 'credit',
                    'amount'         => $order->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => (float) $wallet->balance,
                    'status'         => 'completed',
                    'description'    => "Refund: Number #{$order->activation_id} cancelled",
                    'metadata'       => ['number_order_id' => $order->id],
                ]);
            });

            Log::info("SMS cancelled + refunded: order={$order->id} amount={$order->amount}");

            return response()->json([
                'order'   => $this->formatOrder($order->fresh()->load('smsMessages')),
                'message' => 'Order cancelled and refunded.',
            ]);
        } catch (\Throwable $e) {
            Log::error("SMS cancel failed for order {$order->id}: " . $e->getMessage());
            return response()->json(['error' => 'Cancellation failed. Please try again.'], 500);
        }
    }

    /** POST /sms/orders/{order}/finish */
    public function finish(Request $request, NumberOrder $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Not found.'], 404);
        }
        if ($order->isTerminal()) {
            return response()->json(['error' => 'Order is already completed.'], 422);
        }

        try {
            $driver   = $this->providerService->driver($order->provider);
            $response = $driver->finishOrder($order->activation_id);

            $order->update([
                'status'       => 'FINISHED',
                'completed_at' => now(),
                'raw_response' => $response,
            ]);

            Log::info("SMS finished: order={$order->id}");

            return response()->json([
                'order'   => $this->formatOrder($order->fresh()->load('smsMessages')),
                'message' => 'Order finished.',
            ]);
        } catch (\Throwable $e) {
            Log::error("SMS finish failed for order {$order->id}: " . $e->getMessage());
            return response()->json(['error' => 'Could not finish order. Please try again.'], 500);
        }
    }

    // â”€â”€ Data extraction helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Resolve effective country, operator, and raw provider cost for a purchase request.
     *
     * Returns [effectiveCountry, effectiveOperator, rawProviderCost].
     * Throws RuntimeException when no stock is available.
     */
    private function localServiceQuery(string $service): \Illuminate\Database\Eloquent\Builder
    {
        $needle = trim($service);

        return Service::query()
            ->where('type', 'sms')
            ->where('is_active', true)
            ->where('provider_service_code', $needle);
    }

    private function localCountryRows(): array
    {
        $rows = DB::table('sms_country_summaries')
            ->where('qty', '>', 0)
            ->orderBy('name')
            ->get();

        $countries = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row->code ?? ''));
            if ($code === '' || $code === 'any') {
                continue;
            }

            $countries[$code] = [
                'code' => $code,
                'name' => (string) (($row->name ?? null) ?: Str::of($code)->replace(['_', '-'], ' ')->title()),
            ];
        }

        if ($countries === []) {
            $countries['any'] = ['code' => 'any', 'name' => 'Best Available'];
        }

        return array_values($countries);
    }

    private function resolvePrice(mixed $driver, array $validated): array
    {
        $country  = $validated['country'];
        $operator = $validated['operator'];
        $service  = $validated['service'];

        if (str_starts_with($service, 'pvapins:')) {
            $query = Service::query()
                ->where('type', 'sms')
                ->where('is_active', true)
                ->where('provider_service_code', $service);

            if ($country !== 'any') {
                $query->where('sms_country', $country);
            }

            $row = $query->orderBy('selling_price')->first(['cost_price', 'metadata', 'sms_country']);
            if (!$row) {
                throw new \RuntimeException("No numbers available for {$country} right now.");
            }

            $metadata = is_array($row->metadata) ? $row->metadata : [];
            return [
                (string) ($row->sms_country ?: ($metadata['country'] ?? $country)),
                'any',
                (float) ($row->cost_price ?? 0),
            ];
        }

        if (str_starts_with($service, '5sim:')) {
            $query = Service::query()
                ->where('type', 'sms')
                ->where('is_active', true)
                ->where('provider_service_code', $service);

            if ($country !== 'any') {
                $query->where('sms_country', $country);
            }

            if ($operator !== 'any') {
                $query->where('sms_operator', $operator);
            }

            $row = $query->orderBy('selling_price')->first(['cost_price', 'metadata', 'sms_country', 'sms_operator']);
            if (!$row) {
                throw new \RuntimeException("No numbers available for {$country}/{$operator} right now.");
            }

            $metadata = is_array($row->metadata) ? $row->metadata : [];
            return [
                (string) ($row->sms_country ?: ($metadata['country'] ?? $country)),
                (string) ($row->sms_operator ?: ($metadata['operator'] ?? 'any')),
                (float) ($row->cost_price ?? 0),
            ];
        }

        if ($country === 'any' || $operator === 'any') {
            $raw        = $driver->getAllProducts();
            $firstValue = reset($raw);
            $isFlat     = is_array($firstValue) && (
                array_key_exists('Category', $firstValue) ||
                array_key_exists('Qty', $firstValue) ||
                array_key_exists('Price', $firstValue)
            );

            if ($isFlat && isset($raw[$service])) {
                $cost = (float) ($raw[$service]['Price'] ?? $raw[$service]['price'] ?? 0);
                $rowCountry = trim((string) ($raw[$service]['country'] ?? ''));
                if (str_starts_with($service, 'pvapins:') && $rowCountry !== '') {
                    return [$rowCountry, 'any', $cost];
                }
            } else {
                $cost = PHP_FLOAT_MAX;
                foreach ($raw as $cnt => $ops) {
                    if (!is_array($ops)) continue;
                    foreach ($ops as $op => $serviceList) {
                        if (!is_array($serviceList) || !isset($serviceList[$service])) continue;
                        $p = (float) ($serviceList[$service]['Price']
                            ?? $serviceList[$service]['price']
                            ?? $serviceList[$service]['Cost']
                            ?? $serviceList[$service]['cost'] ?? 0);
                        if ($p > 0 && $p < $cost) $cost = $p;
                    }
                }
                if ($cost === PHP_FLOAT_MAX) $cost = 0;
            }

            return ['any', 'any', $cost];
        }

        // Specific country/operator â€” use getPrices()
        $pricesRaw = $driver->getPrices($service);

        // Unwrap product-keyed response: { serviceName: { country: { op: {...} } } }
        if (count($pricesRaw) === 1 && array_key_first($pricesRaw) === $service) {
            $pricesData = $pricesRaw[$service];
        } else {
            $pricesData = $pricesRaw;
        }

        if (empty($pricesData) || !isset($pricesData[$country])) {
            throw new \RuntimeException("No numbers available for {$country} right now.");
        }

        $countryOps = $pricesData[$country];

        if (isset($countryOps[$operator]) && ((int) ($countryOps[$operator]['count'] ?? 0)) > 0) {
            return [$country, $operator, (float) ($countryOps[$operator]['cost'] ?? 0)];
        }

        // Requested operator unavailable â€” find cheapest in-stock operator
        $bestOp   = null;
        $bestCost = PHP_FLOAT_MAX;
        foreach ($countryOps as $opName => $opData) {
            if (!is_array($opData)) continue;
            $cnt = (int)   ($opData['count'] ?? 0);
            $cst = (float) ($opData['cost']  ?? 0);
            if ($cnt > 0 && $cst > 0 && $cst < $bestCost) {
                $bestCost = $cst;
                $bestOp   = $opName;
            }
        }

        if (!$bestOp) {
            throw new \RuntimeException("No numbers available for {$country}/{$operator} right now.");
        }

        return [$country, $bestOp, $bestCost];
    }

    /**
     * Extract a flat list of all services from the raw any/any response.
     * Handles both known 5SIM response structures.
     */
    private function extractServices(array $raw, float $markupPercent): array
    {
        if (empty($raw)) return [];

        $firstValue = reset($raw);

        // Structure A (flat): { service_id: { Category, Qty, Price } }
        if (is_array($firstValue) && (
            array_key_exists('Category', $firstValue) ||
            array_key_exists('Qty', $firstValue) ||
            array_key_exists('Price', $firstValue)
        )) {
            $services = [];
            foreach ($raw as $id => $info) {
                if (!is_array($info)) continue;
                $sid      = (string) $id;
                $rawPrice = (float) ($info['Price'] ?? $info['price'] ?? 0);
                $services[] = [
                    'id'       => $sid,
                    'label'    => (string) $this->formatServiceLabel($sid),
                    'category' => (string) ($info['Category'] ?? $info['category'] ?? 'Other'),
                    'qty'      => (int) ($info['Qty'] ?? $info['qty'] ?? 0),
                    'price'    => round($rawPrice * (1 + $markupPercent / 100), 4),
                ];
            }
            return $this->sortServices($services);
        }

        // Structure B (nested): { country: { operator: { service: { Category, Qty, Price } } } }
        $serviceMap = [];
        foreach ($raw as $country => $operators) {
            if (!is_array($operators)) continue;
            foreach ($operators as $operator => $serviceList) {
                if (!is_array($serviceList)) continue;
                foreach ($serviceList as $id => $info) {
                    if (!is_array($info)) continue;
                    $sid = (string) $id;
                    if (!isset($serviceMap[$sid])) {
                        $serviceMap[$sid] = [
                            'id'       => $sid,
                            'label'    => (string) $this->formatServiceLabel($sid),
                            'category' => (string) ($info['Category'] ?? $info['category'] ?? 'Other'),
                            'qty'      => 0,
                            'min_raw'  => PHP_FLOAT_MAX,
                        ];
                    }
                    $qty = (int) ($info['Qty'] ?? $info['qty'] ?? $info['count'] ?? 0);
                    $price = (float) ($info['Price'] ?? $info['price'] ?? $info['Cost'] ?? $info['cost'] ?? 0);
                    $serviceMap[$sid]['qty'] += $qty;
                    if ($price > 0 && $price < $serviceMap[$sid]['min_raw']) {
                        $serviceMap[$sid]['min_raw'] = $price;
                    }
                }
            }
        }

        $services = [];
        foreach ($serviceMap as $data) {
            $rawPrice = ($data['min_raw'] === PHP_FLOAT_MAX) ? 0 : $data['min_raw'];
            $services[] = [
                'id'       => $data['id'],
                'label'    => $data['label'],
                'category' => $data['category'],
                'qty'      => $data['qty'],
                'price'    => round($rawPrice * (1 + $markupPercent / 100), 4),
            ];
        }
        return $this->sortServices($services);
    }

    /**
     * Extract country/operator options for a specific service.
     * Only works when the any/any response is nested (Structure B).
     */
    private function extractCountryOptions(array $raw, string $service, float $markupPercent): array
    {
        if (empty($raw)) return [];

        $firstValue = reset($raw);

        // Structure A (flat) â€” no country breakdown available
        if (is_array($firstValue) && (
            array_key_exists('Category', $firstValue) ||
            array_key_exists('Qty', $firstValue) ||
            array_key_exists('Price', $firstValue)
        )) {
            Log::info("5SIM response is flat â€” no per-country data for {$service}");
            return [];
        }

        // Structure B (nested)
        $options = [];
        foreach ($raw as $country => $operators) {
            if (!is_array($operators)) continue;
            foreach ($operators as $operator => $serviceList) {
                if (!is_array($serviceList) || !isset($serviceList[$service])) continue;
                $info  = $serviceList[$service];
                $count = (int) ($info['Qty'] ?? $info['qty'] ?? $info['count'] ?? 0);
                if ($count === 0) continue;
                $rawCost = (float) ($info['Price'] ?? $info['price'] ?? $info['Cost'] ?? $info['cost'] ?? 0);
                $options[] = [
                    'country'       => $country,
                    'operator'      => $operator,
                    'count'         => $count,
                    'provider_cost' => $rawCost,
                    'cost'          => round($rawCost * (1 + $markupPercent / 100), 4),
                ];
            }
        }

        usort($options, fn ($a, $b) => $b['count'] <=> $a['count']);
        return array_values($options);
    }

    /** Convert a 5SIM service ID to a human-readable label. */
    private function formatServiceLabel(string $id): string
    {
        $map = [
            'telegram'   => 'Telegram',
            'whatsapp'   => 'WhatsApp',
            'google'     => 'Google',
            'discord'    => 'Discord',
            'openai'     => 'OpenAI',
            'tiktok'     => 'TikTok',
            'instagram'  => 'Instagram',
            'facebook'   => 'Facebook',
            'twitter'    => 'Twitter / X',
            'amazon'     => 'Amazon',
            'microsoft'  => 'Microsoft',
            'binance'    => 'Binance',
            'bybit'      => 'Bybit',
            'okx'        => 'OKX',
            'paypal'     => 'PayPal',
            'netflix'    => 'Netflix',
            'apple'      => 'Apple',
            'uber'       => 'Uber',
            'linkedin'   => 'LinkedIn',
            'snapchat'   => 'Snapchat',
            'steam'      => 'Steam',
            'coinbase'   => 'Coinbase',
            'wise'       => 'Wise',
            'revolut'    => 'Revolut',
            'airbnb'     => 'Airbnb',
            'ebay'       => 'eBay',
            'tinder'     => 'Tinder',
            'viber'      => 'Viber',
            'line'       => 'LINE',
            'wechat'     => 'WeChat',
            'kakao'      => 'KakaoTalk',
            'yahoo'      => 'Yahoo',
            'shopify'    => 'Shopify',
            'stripe'     => 'Stripe',
            'nike'       => 'Nike',
            'vk'         => 'VK',
            'ok'         => 'Odnoklassniki',
            'avito'      => 'Avito',
            'wildberries'=> 'Wildberries',
            'ozon'       => 'Ozon',
            'lazada'     => 'Lazada',
            'shopee'     => 'Shopee',
            'grab'       => 'Grab',
            'gojek'      => 'Gojek',
        ];

        return $map[$id] ?? ucwords(str_replace(['-', '_'], ' ', $id));
    }

    // â”€â”€ Shared output formatter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function formatOrder(NumberOrder $order): array
    {
        return [
            'id'             => $order->id,
            'activation_id'  => $order->activation_id,
            'service'        => $order->service,
            'country'        => $order->country,
            'operator'       => $order->operator,
            'phone_number'   => $order->phone_number,
            'provider_cost'  => (float) $order->provider_cost,
            'markup_percent' => (float) $order->markup_percent,
            'amount'         => (float) $order->amount,
            'balance_before' => (float) $order->balance_before,
            'balance_after'  => (float) $order->balance_after,
            'status'         => $order->status,
            'otp_code'       => $order->otp_code,
            'sms_text'       => $order->sms_text,
            'expires_at'     => $order->expires_at?->toIso8601String(),
            'completed_at'   => $order->completed_at?->toIso8601String(),
            'created_at'     => $order->created_at->toIso8601String(),
            'sms_messages'   => ($order->relationLoaded('smsMessages')
                ? $order->smsMessages->map(fn ($m) => [
                    'id'          => $m->id,
                    'sender'      => $m->sender,
                    'message'     => $m->message,
                    'code'        => $m->code,
                    'received_at' => $m->received_at->toIso8601String(),
                ])->values()->all()
                : []),
        ];
    }
}
