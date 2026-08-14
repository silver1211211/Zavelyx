<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationBroadcast;
use App\Models\Order;
use App\Models\PaymentInvoice;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        // Weekly order counts for chart (last 7 days)
        $weeklyOrders = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();
            return [
                'date'  => now()->subDays($daysAgo)->format('D'),
                'count' => Order::whereDate('created_at', $date)->count(),
            ];
        })->values();

        // Monthly revenue (last 6 months)
        $monthlyRevenue = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            return [
                'month'   => $date->format('M'),
                'revenue' => (float) Transaction::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('type', 'deposit')
                    ->where('status', 'successful')
                    ->sum('amount'),
            ];
        })->values();

        $providers = Provider::withCount('services')
            ->orderByDesc('is_active')
            ->orderBy('priority')
            ->get(['id', 'name', 'is_active', 'balance', 'last_synced_at', 'priority'])
            ->map(fn($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'is_active'      => (bool) $p->is_active,
                'balance'        => $p->balance !== null ? (float) $p->balance : null,
                'services_count' => $p->services_count,
                'last_synced_at' => $p->last_synced_at?->toISOString(),
                'priority'       => $p->priority,
            ]);

        $pendingRefunds = Order::whereIn('status', ['canceled', 'partial', 'failed'])
            ->whereNull('refund_status')
            ->count();

        $failedToday = Order::where('status', 'failed')
            ->whereDate('updated_at', today())
            ->count();

        $liveOrders = Order::with(['user:id,name', 'service:id,name'])
            ->whereIn('status', ['pending', 'processing'])
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'service_id', 'amount', 'status', 'quantity', 'created_at'])
            ->map(fn($o) => [
                'id'      => $o->id,
                'user'    => $o->user?->name,
                'service' => $o->service?->name,
                'amount'  => (float) $o->amount,
                'status'  => $o->status,
                'quantity' => (int) $o->quantity,
                'created_at' => $o->created_at->toISOString(),
            ]);

        $notifDelivered = Notification::count();
        $notifRead      = Notification::where('is_read', true)->count();
        $notifUnread    = $notifDelivered - $notifRead;
        $readRate       = $notifDelivered > 0 ? round($notifRead / $notifDelivered * 100, 1) : 0;

        $topBroadcast = NotificationBroadcast::whereNotNull('sent_at')
            ->orderByDesc('recipients_count')
            ->first(['id', 'title', 'recipients_count', 'sent_at']);

        $recentBroadcasts = NotificationBroadcast::with('creator:id,name')
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->limit(5)
            ->get(['id', 'title', 'type', 'priority', 'recipients_count', 'sent_at', 'created_by'])
            ->map(fn ($b) => [
                'id'               => $b->id,
                'title'            => $b->title,
                'type'             => $b->type,
                'priority'         => $b->priority,
                'recipients_count' => $b->recipients_count,
                'sent_at'          => $b->sent_at?->toISOString(),
                'creator'          => $b->creator?->name,
                'read'             => Notification::where('broadcast_id', $b->id)->where('is_read', true)->count(),
            ]);

        // ── Deposit analytics ──────────────────────────────────────────────
        $depositStats = [
            'total'           => PaymentInvoice::count(),
            'successful'      => PaymentInvoice::where('status', 'finished')->count(),
            'credited'        => PaymentInvoice::whereNotNull('credited_at')->count(),
            'pending'         => PaymentInvoice::whereIn('status', PaymentInvoice::PENDING_STATUSES)->count(),
            'failed'          => PaymentInvoice::whereIn('status', ['failed', 'expired'])->count(),
            'totalVolume'     => (float) PaymentInvoice::where('status', 'finished')->sum('price_amount'),
            'todayVolume'     => (float) PaymentInvoice::where('status', 'finished')->whereDate('created_at', today())->sum('price_amount'),
            'todayCount'      => PaymentInvoice::where('status', 'finished')->whereDate('created_at', today())->count(),
            'failedCallbacks' => PaymentInvoice::where('status', 'finished')->whereNull('credited_at')->count(),
            'successRate'     => PaymentInvoice::count() > 0
                ? round(PaymentInvoice::where('status', 'finished')->count() / PaymentInvoice::count() * 100, 1)
                : 0,
            'avgProcessSeconds' => (float) PaymentInvoice::whereNotNull('credited_at')
                ->whereNotNull('callback_received_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, callback_received_at, credited_at)) as avg_sec')
                ->value('avg_sec'),
        ];

        $depositDaily = collect(range(6, 0))->map(function ($d) {
            $date = now()->subDays($d)->toDateString();
            return [
                'date'   => now()->subDays($d)->format('D'),
                'count'  => PaymentInvoice::whereDate('created_at', $date)->count(),
                'volume' => (float) PaymentInvoice::where('status', 'finished')->whereDate('created_at', $date)->sum('price_amount'),
            ];
        })->values();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers'       => User::count(),
                'totalOrders'      => Order::count(),
                'pendingOrders'    => Order::where('status', 'pending')->count(),
                'processingOrders' => Order::where('status', 'processing')->count(),
                'completedOrders'  => Order::where('status', 'completed')->count(),
                'revenue'          => (float) Transaction::where('type', 'deposit')->where('status', 'successful')->sum('amount'),
                'activeServices'   => Service::where('is_active', true)->count(),
                'newUsersToday'    => User::whereDate('created_at', today())->count(),
                'pendingRefunds'   => $pendingRefunds,
                'failedToday'      => $failedToday,
            ],
            'notifStats' => [
                'delivered'        => $notifDelivered,
                'read'             => $notifRead,
                'unread'           => $notifUnread,
                'read_rate'        => $readRate,
                'totalBroadcasts'  => NotificationBroadcast::whereNotNull('sent_at')->count(),
                'scheduled'        => NotificationBroadcast::whereNull('sent_at')->whereNotNull('scheduled_at')->count(),
                'activeCampaigns'  => NotificationBroadcast::whereNotNull('sent_at')->where('sent_at', '>=', now()->subDays(30))->count(),
                'topBroadcast'     => $topBroadcast,
            ],
            'recentBroadcasts' => $recentBroadcasts,
            'recentOrders' => Order::with(['user:id,name,email', 'service:id,name'])
                ->latest()
                ->limit(8)
                ->get(['id', 'reference', 'user_id', 'service_id', 'amount', 'status', 'created_at']),
            'recentUsers'   => User::latest()->limit(6)->get(['id', 'name', 'email', 'created_at']),
            'weeklyOrders'   => $weeklyOrders,
            'monthlyRevenue' => $monthlyRevenue,
            'providers'      => $providers,
            'liveOrders'     => $liveOrders,
            'depositStats'   => $depositStats,
            'depositDaily'   => $depositDaily,
        ]);
    }
}
