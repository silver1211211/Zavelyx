<?php

namespace App\Http\Controllers;

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
        'whatsapp', 'kwai', 'kick', 'rutube', 'rednote', 'jaco', 'quora',
        'coinmarketcap', 'other',
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
                ->filter(fn ($c) => $c > 0)
                ->map(fn ($c, $k) => ['key' => $k, 'count' => (int) $c])
                ->sortByDesc('count')
                ->values()
                ->all();
        });

        return Inertia::render('Dashboard', [
            'balance'         => (float) ($user->wallet?->balance ?? 0),
            'stats'           => $stats,
            'platforms'       => $platforms,
        ]);
    }
}
