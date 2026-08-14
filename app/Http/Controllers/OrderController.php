<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOrderJob;
use App\Models\Order;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private const PLATFORM_KEYS = [
        'tiktok', 'youtube', 'telegram', 'spotify', 'crypto', 'google',
        'instagram', 'facebook', 'twitter', 'twitch', 'website', 'linkedin',
        'soundcloud', 'traffic', 'threads', 'discord', 'seo', 'reddit', 'pinterest',
        'whatsapp', 'kwai', 'kick', 'rutube', 'rednote', 'jaco', 'quora',
        'coinmarketcap', 'other',
    ];

    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->with(['service:id,name', 'provider:id,name'])
            ->latest()
            ->paginate(20)
            ->through(fn($o) => [
                'id'                => $o->id,
                'reference'         => $o->reference,
                'service'           => ['name' => $o->service?->name],
                'provider_name'     => $o->provider?->name,
                'link'              => $o->link,
                'quantity'          => (int) ($o->quantity ?? 0),
                'amount'            => (float) $o->amount,
                'status'            => $o->status,
                'start_count'       => (int) ($o->start_count ?? 0),
                'remains'           => (int) ($o->remains ?? 0),
                'provider_order_id' => $o->provider_order_id,
                'refund_status'     => $o->refund_status,
                'refund_amount'     => $o->refund_amount !== null ? (float) $o->refund_amount : null,
                'refunded_at'       => $o->refunded_at?->toISOString(),
                'last_synced_at'    => $o->last_synced_at?->toISOString(),
                'processed_at'      => $o->processed_at?->toISOString(),
                'created_at'        => $o->created_at->toISOString(),
            ]);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function create(): Response
    {
        // Lightweight: only category metadata for building platform cards.
        // Services are fetched lazily via loadServices() on demand.
        $categories = Cache::remember('order.categories', 600, fn () =>
            DB::table('services as s')
                ->join('categories as c', 's.category_id', '=', 'c.id')
                ->leftJoin('providers as p', 's.provider_id', '=', 'p.id')
                ->where('s.is_active', true)
                ->where('s.type', 'smm')
                ->where(function ($q): void {
                    $q->whereNull('s.provider_id')
                      ->orWhere('p.is_active', true);
                })
                ->select('c.id', 'c.name', 'c.slug', DB::raw('COUNT(s.id) as count'))
                ->groupBy('c.id', 'c.name', 'c.slug')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($c) => [
                    'id'    => $c->id,
                    'name'  => $c->name,
                    'slug'  => $c->slug,
                    'count' => (int) $c->count,
                ])
        );

        return Inertia::render('Orders/Create', [
            'categories' => $categories,
            'platforms' => $this->platformCounts(),
        ]);
    }

    private function platformCounts(): array
    {
        return Cache::remember('order.platform_counts', 600, function () {
            $rows = Service::available()
                ->where('services.type', 'smm')
                ->join('categories', 'categories.id', '=', 'services.category_id')
                ->get([
                    'services.name as service_name',
                    'categories.name as category_name',
                ]);

            $totals = array_fill_keys(self::PLATFORM_KEYS, 0);
            foreach ($rows as $row) {
                $haystack = strtolower((string) $row->category_name . ' ' . (string) $row->service_name);
                $normalized = preg_replace('/[^a-z0-9]+/', '', $haystack);
                $matched = false;
                foreach (self::PLATFORM_KEYS as $key) {
                    if ($key !== 'other' && (str_contains($haystack, $key) || str_contains($normalized, $key))) {
                        $totals[$key]++;
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    $totals['other']++;
                }
            }

            return collect($totals)
                ->filter(fn ($count) => $count > 0)
                ->map(fn ($count, $key) => ['key' => $key, 'count' => (int) $count])
                ->sortByDesc('count')
                ->values()
                ->all();
        });
    }

    public function loadServices(Request $request): JsonResponse
    {
        $platform = strtolower(trim((string) $request->string('platform', '')));
        $search = mb_strtolower(trim((string) $request->string('q', '')));

        if ($platform === '') {
            return response()->json([]);
        }

        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        $services = Cache::remember(
            'order.svcs.'.sha1($platform.'|'.$search),
            300,
            function () use ($platform, $search) {
                $query = Service::available()
                    ->where('services.type', 'smm')
                    ->with('category:id,name,slug')
                    ->orderBy('category_id')
                    ->orderBy('selling_price');

                if ($platform === 'other') {
                    $known = array_values(array_filter(self::PLATFORM_KEYS, fn ($key) => $key !== 'other'));
                    $query->where(function (Builder $q) use ($known): void {
                        foreach ($known as $key) {
                            $terms = $key === 'rednote' ? ['red note', 'xiaohongshu', 'rednote'] : [$key];
                            foreach ($terms as $term) {
                                $q->whereRaw('LOWER(services.name) NOT LIKE ?', ["%{$term}%"])
                                  ->whereDoesntHave('category', fn (Builder $cat) =>
                                      $cat->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                                  );
                            }
                        }
                    });
                } elseif ($platform !== 'all') {
                    $terms = $platform === 'rednote' ? ['red note', 'xiaohongshu', 'rednote'] : [$platform];
                    $query->where(function (Builder $q) use ($terms): void {
                        foreach ($terms as $term) {
                            $q->orWhereRaw('LOWER(services.name) LIKE ?', ["%{$term}%"])
                              ->orWhereHas('category', fn (Builder $cat) =>
                                  $cat->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                              );
                        }
                    });
                }

                if ($search !== '') {
                    $terms = array_values(array_filter(
                        preg_split('/\s+/u', $search) ?: [],
                        fn (string $term): bool => $term !== ''
                    ));

                    foreach ($terms as $term) {
                        $like = '%'.addcslashes($term, '%_\\').'%';
                        $query->where(function (Builder $q) use ($like, $term): void {
                            $q->whereRaw("LOWER(services.name) LIKE ? ESCAPE '\\\\'", [$like])
                              ->orWhereHas('category', fn (Builder $cat) =>
                                  $cat->whereRaw("LOWER(name) LIKE ? ESCAPE '\\\\'", [$like])
                              );

                            if (ctype_digit($term)) {
                                $q->orWhere('services.id', (int) $term);
                            }
                        });
                    }
                }

                return $query
                    ->limit(750)
                    ->get(['id', 'category_id', 'name', 'selling_price', 'min_amount', 'max_amount', 'metadata'])
                    ->map(fn ($s) => [
                        'id'            => $s->id,
                        'name'          => $s->name,
                        'category_id'   => $s->category_id,
                        'category'      => $s->category
                            ? ['id' => $s->category->id, 'name' => $s->category->name]
                            : null,
                        'selling_price' => (float) $s->selling_price,
                        'min_amount'    => (int) ($s->min_amount ?? 1),
                        'max_amount'    => (int) ($s->max_amount ?? 1_000_000),
                        'metadata'      => is_array($s->metadata) ? $s->metadata : [],
                    ])
                    ->values();
            }
        );

        return response()->json($services);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'link'       => ['required', 'string', 'max:500'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        // Normalize URL — auto-prepend https:// if scheme is missing
        $link = trim($validated['link']);
        if (!preg_match('/^https?:\/\//i', $link)) {
            $link = 'https://' . $link;
        }
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return back()->withErrors(['link' => 'Please enter a valid URL (e.g. https://instagram.com/p/...).']);
        }

        $service = Service::with(['provider', 'category'])->findOrFail($validated['service_id']);

        if (!$service->is_active || ($service->provider_id && !$service->provider?->is_active)) {
            return back()->withErrors(['service_id' => 'This service is currently unavailable.']);
        }

        $quantity = (int) $validated['quantity'];

        // Validate quantity against service limits
        $min = (int) ($service->min_amount ?? 1);
        $max = (int) ($service->max_amount ?? 10_000_000);
        if ($quantity < $min || $quantity > $max) {
            return back()->withErrors([
                'quantity' => "Quantity must be between {$min} and {$max}.",
            ]);
        }

        // SMM pricing: rate is per 1,000 units → total = (qty / 1000) × rate
        $amount = round(($quantity / 1000) * (float) $service->selling_price, 8);

        $wallet = $request->user()->wallet;

        if (!$wallet || !$wallet->is_active) {
            return back()->withErrors(['balance' => 'Your account is currently frozen. Contact support.']);
        }

        if ((float) $wallet->balance < $amount) {
            return back()->withErrors(['balance' => 'Insufficient balance. Please top up your wallet.']);
        }

        $order = null;

        DB::transaction(function () use ($request, $service, $validated, $link, $amount, $wallet, $quantity, &$order) {
            $balanceBefore = (float) $wallet->balance;

            $wallet->decrement('balance', $amount);
            $wallet->refresh();

            $order = Order::create([
                'reference'   => Str::uuid(),
                'user_id'     => $request->user()->id,
                'service_id'  => $service->id,
                'provider_id' => $service->provider_id,
                'amount'      => $amount,
                'status'      => 'pending',
                'quantity'    => $quantity,
                'link'        => $link,
                'payload'     => [
                    'link'        => $link,
                    'quantity'    => $quantity,
                    'rate_per_1k' => (float) $service->selling_price,
                    'cost_per_1k' => (float) ($service->cost_price ?? 0),
                ],
            ]);

            Transaction::create([
                'reference'      => Str::uuid(),
                'user_id'        => $request->user()->id,
                'wallet_id'      => $wallet->id,
                'order_id'       => $order->id,
                'type'           => 'debit',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'status'         => 'completed',
                'description'    => "Order #{$order->id}: {$service->name}",
            ]);
        });

        if ($order) {
            // Dispatch provider submission (runs synchronously in dev)
            ProcessOrderJob::dispatch($order);

            // Refresh to capture any provider updates from the sync job
            $order->refresh();
            $wallet->refresh();

            Inertia::flash('order_placed', [
                'order_id'          => $order->id,
                'service_name'      => $service->name,
                'category_name'     => $service->category?->name ?? '',
                'quantity'          => $quantity,
                'amount'            => $amount,
                'remaining_balance' => (float) $wallet->balance,
                'provider_order_id' => $order->provider_order_id,
                'status'            => $order->status,
                'link'              => $link,
                'provider_error'    => $order->provider_response['placement_error'] ?? null,
            ]);

            return redirect()->route('orders.create');
        }

        return redirect()->route('orders.create')->with('error', 'Something went wrong placing your order.');
    }
}
