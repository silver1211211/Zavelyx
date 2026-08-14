<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NumberOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NumberOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = NumberOrder::with(['user', 'provider', 'smsMessages' => fn ($q) => $q->orderBy('received_at')])
            ->orderByDesc('created_at');

        // ── Filters ──────────────────────────────────────────────────────────

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('activation_id', 'like', "%{$search}%")
                  ->orWhere('service', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%"))
                  ->orWhereHas('provider', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        // scope = quick filter tab: all | successful | pending | cancelled | failed
        if ($scope = $request->query('scope')) {
            match ($scope) {
                'successful' => $query->where('status', 'FINISHED'),
                'pending'    => $query->whereIn('status', ['PENDING', 'RECEIVED']),
                'cancelled'  => $query->where('status', 'CANCELLED'),
                'failed'     => $query->whereIn('status', ['BANNED', 'EXPIRED', 'TIMEOUT']),
                default      => null,
            };
        } elseif ($status = $request->query('status')) {
            $query->where('status', strtoupper($status));
        }

        if ($service = $request->query('service')) {
            $query->where('service', $service);
        }

        if ($provider = $request->query('provider_id')) {
            $query->where('number_provider_id', $provider);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // ── Paginate ─────────────────────────────────────────────────────────

        $orders = $query->paginate(50)->through(fn (NumberOrder $o) => $this->formatOrderRow($o));

        // ── Aggregate stats (respects same filters) ───────────────────────────

        $statsQuery = NumberOrder::query();

        if ($search = $request->query('search')) {
            $statsQuery->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('activation_id', 'like', "%{$search}%")
                  ->orWhere('service', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%"))
                  ->orWhereHas('provider', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        if ($service = $request->query('service')) {
            $statsQuery->where('service', $service);
        }

        if ($provider = $request->query('provider_id')) {
            $statsQuery->where('number_provider_id', $provider);
        }

        if ($dateFrom = $request->query('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $dateTo);
        }

        // Stats are always over ALL orders (ignoring scope/status filter) so the
        // cards always show the full picture for the active search/date context.
        $stats = $statsQuery->selectRaw('
            COUNT(*)                                                                                   AS total_orders,
            SUM(CASE WHEN status = "FINISHED"                              THEN 1 ELSE 0 END)          AS successful,
            SUM(CASE WHEN status IN ("PENDING","RECEIVED")                 THEN 1 ELSE 0 END)          AS pending_count,
            SUM(CASE WHEN status = "CANCELLED"                             THEN 1 ELSE 0 END)          AS cancelled_count,
            SUM(CASE WHEN status = "CANCELLED"                             THEN 1 ELSE 0 END)          AS refunded_count,
            SUM(CASE WHEN status IN ("BANNED","EXPIRED","TIMEOUT")         THEN 1 ELSE 0 END)          AS failed_count,
            ROUND(SUM(CASE WHEN status = "FINISHED" THEN amount           ELSE 0 END), 4)             AS realized_revenue,
            ROUND(SUM(CASE WHEN status = "FINISHED" THEN provider_cost    ELSE 0 END), 4)             AS realized_cost,
            ROUND(SUM(CASE WHEN status = "FINISHED" THEN amount - provider_cost ELSE 0 END), 4)       AS net_profit,
            ROUND(SUM(amount),        4)                                                               AS gross_revenue,
            ROUND(SUM(provider_cost), 4)                                                               AS gross_cost,
            ROUND(SUM(CASE WHEN status = "CANCELLED" THEN amount          ELSE 0 END), 4)             AS total_refunded
        ')->first();

        return Inertia::render('Admin/NumberOrders', [
            'orders'  => $orders,
            'stats'   => $stats,
            'filters' => $request->only(['search', 'scope', 'status', 'service', 'provider_id', 'date_from', 'date_to']),
        ]);
    }

    public function show(NumberOrder $numberOrder): Response
    {
        $numberOrder->load(['user', 'provider', 'smsMessages' => fn ($q) => $q->orderBy('received_at')]);

        return Inertia::render('Admin/NumberOrderShow', [
            'order' => $this->formatOrderFull($numberOrder),
        ]);
    }

    // ── Compact row format for table ─────────────────────────────────────────

    private function formatOrderRow(NumberOrder $o): array
    {
        $profit    = round((float) $o->amount - (float) $o->provider_cost, 4);
        $profitPct = (float) $o->amount > 0
            ? round($profit / (float) $o->amount * 100, 1)
            : 0;

        $firstSms = $o->smsMessages->first();

        return [
            'id'              => $o->id,
            'activation_id'   => $o->activation_id,
            'service'         => $o->service,
            'country'         => $o->country,
            'operator'        => $o->operator,
            'phone_number'    => $o->phone_number,
            'provider_cost'   => (float) $o->provider_cost,
            'markup_percent'  => (float) $o->markup_percent,
            'amount'          => (float) $o->amount,
            'profit'          => $profit,
            'profit_pct'      => $profitPct,
            'balance_before'  => (float) $o->balance_before,
            'balance_after'   => (float) $o->balance_after,
            'status'          => $o->status,
            'otp_code'        => $o->otp_code,
            'sms_text'        => $o->sms_text,
            'sms_count'       => $o->smsMessages->count(),
            'is_refunded'     => $o->status === 'CANCELLED',
            'sms_received_at' => $firstSms?->received_at?->toIso8601String(),
            'expires_at'      => $o->expires_at?->toIso8601String(),
            'completed_at'    => $o->completed_at?->toIso8601String(),
            'created_at'      => $o->created_at->toIso8601String(),
            'user'            => [
                'id'    => $o->user->id,
                'name'  => $o->user->name,
                'email' => $o->user->email,
            ],
            'provider'        => [
                'id'   => $o->provider->id,
                'name' => $o->provider->name,
            ],
            'sms_messages'    => $o->smsMessages->map(fn ($m) => [
                'id'          => $m->id,
                'sender'      => $m->sender,
                'message'     => $m->message,
                'code'        => $m->code,
                'received_at' => $m->received_at->toIso8601String(),
            ])->values()->all(),
            'raw_response'    => $o->raw_response,
        ];
    }

    // ── Full format for show page ─────────────────────────────────────────────

    private function formatOrderFull(NumberOrder $o): array
    {
        return array_merge($this->formatOrderRow($o), [
            'raw_response' => $o->raw_response,
        ]);
    }
}
