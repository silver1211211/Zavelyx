<?php

namespace App\Http\Controllers;

use App\Models\NumberOrder;
use App\Models\Order;
use App\Models\PaymentInvoice;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const PLATFORM_KEYS = [
        'tiktok', 'youtube', 'telegram', 'spotify', 'crypto', 'google',
        'instagram', 'facebook', 'twitter', 'twitch', 'website', 'linkedin',
        'soundcloud', 'traffic', 'threads', 'discord', 'seo', 'reddit', 'pinterest',
    ];

    public function __invoke(Request $request): Response
    {
        $user = $request->user()->loadMissing('wallet');

        // Per-user stats — 5-minute cache, invalidated by TTL
        $stats = Cache::remember("dash_stats_{$user->id}", 300, function () use ($user) {
            $row = $user->orders()
                ->select(DB::raw('
                    COUNT(*) as total,
                    SUM(status = "completed") as completed,
                    SUM(status = "processing") as processing,
                    SUM(status = "pending") as pending
                '))
                ->first();

            return [
                'total'      => (int) ($row->total      ?? 0),
                'completed'  => (int) ($row->completed  ?? 0),
                'processing' => (int) ($row->processing ?? 0),
                'pending'    => (int) ($row->pending    ?? 0),
            ];
        });

        // Per-user recent deposits — 3-minute cache
        $recentDeposits = Cache::remember("dash_deposits_{$user->id}", 180, function () use ($user) {
            return PaymentInvoice::where('user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(['id', 'gateway', 'price_amount', 'pay_currency', 'status', 'created_at'])
                ->map(fn ($d) => [
                    'id'           => $d->id,
                    'gateway'      => $d->gateway,
                    'amount'       => (float) $d->price_amount,
                    'pay_currency' => $d->pay_currency,
                    'status'       => $d->status,
                    'created_at'   => $d->created_at->toISOString(),
                ])
                ->values()
                ->all();
        });

        // Site-wide popular services — 10-minute cache (global, not per user)
        $popularServices = Cache::remember('dash_popular_services', 600, function () {
            return DB::table('orders')
                ->join('services', 'services.id', '=', 'orders.service_id')
                ->select('services.id', 'services.name', DB::raw('COUNT(orders.id) as total_orders'))
                ->groupBy('services.id', 'services.name')
                ->orderByDesc('total_orders')
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'id'           => $s->id,
                    'name'         => $s->name,
                    'total_orders' => (int) $s->total_orders,
                ])
                ->all();
        });

        // Platform grid data — 15-minute cache (global).
        // One GROUP BY query replacing the previous full service load (10k+ rows).
        $platforms = Cache::remember('dash_smm_platforms', 900, function () {
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
                foreach (self::PLATFORM_KEYS as $key) {
                    if (str_contains($haystack, $key)) {
                        $totals[$key]++;
                        break;
                    }
                }
            }

            return collect($totals)
                ->filter(fn ($c) => $c > 0)
                ->map(fn ($c, $k) => ['key' => $k, 'count' => (int) $c])
                ->sortByDesc('count')
                ->values()
                ->all();
        });

        // Active SMS numbers — no cache (real-time)
        $activeNumbers = NumberOrder::where('user_id', $user->id)
            ->whereIn('status', [NumberOrder::STATUS_PENDING, NumberOrder::STATUS_RECEIVED])
            ->latest()
            ->limit(4)
            ->get(['id', 'phone_number', 'service', 'country', 'operator', 'status', 'expires_at', 'otp_code', 'amount'])
            ->map(fn ($n) => [
                'id'           => $n->id,
                'phone_number' => $n->phone_number,
                'service'      => $n->service,
                'country'      => $n->country,
                'operator'     => $n->operator,
                'status'       => $n->status,
                'expires_at'   => $n->expires_at?->toISOString(),
                'otp_code'     => $n->otp_code,
                'amount'       => (float) $n->amount,
            ])
            ->values()
            ->all();

        // Recent SMM orders — no cache (real-time)
        $recentOrders = Order::where('user_id', $user->id)
            ->with('service:id,name')
            ->latest()
            ->limit(5)
            ->get(['id', 'service_id', 'quantity', 'amount', 'status', 'link', 'created_at'])
            ->map(fn ($o) => [
                'id'           => $o->id,
                'service_name' => $o->service?->name ?? '—',
                'quantity'     => (int) $o->quantity,
                'amount'       => (float) $o->amount,
                'status'       => $o->status,
                'link'         => $o->link,
                'created_at'   => $o->created_at->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'balance'         => (float) ($user->wallet?->balance ?? 0),
            'stats'           => $stats,
            'platforms'       => $platforms,
            'recentDeposits'  => $recentDeposits,
            'popularServices' => $popularServices,
            'activeNumbers'   => $activeNumbers,
            'recentOrders'    => $recentOrders,
        ]);
    }
}
