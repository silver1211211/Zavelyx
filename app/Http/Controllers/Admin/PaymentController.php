<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDepositJob;
use App\Models\BalanceAdjustment;
use App\Models\DepositLog;
use App\Models\PaymentInvoice;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PaymentController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): InertiaResponse
    {
        $status   = $request->get('status', '');
        $search   = $request->get('search', '');
        $gateway  = $request->get('gateway', '');
        $currency = $request->get('currency', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo   = $request->get('date_to', '');

        $query = PaymentInvoice::with('user:id,name,email')->latest();

        // Status filter
        if ($status) {
            match ($status) {
                'pending'   => $query->whereIn('status', PaymentInvoice::PENDING_STATUSES),
                'completed' => $query->where('status', 'finished'),
                'failed'    => $query->whereIn('status', ['failed', 'expired', 'refunded', 'partially_paid']),
                default     => $query->where('status', $status),
            };
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                  ->orWhere('blockchain_hash', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u
                      ->where('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        // Gateway filter
        if ($gateway) {
            $query->where('gateway', $gateway);
        }

        // Currency filter
        if ($currency) {
            $query->where('pay_currency', strtolower($currency));
        }

        // Date range filter
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $invoices = $query->paginate(25)->withQueryString();

        // Count summaries
        $counts = [
            'all'       => PaymentInvoice::count(),
            'pending'   => PaymentInvoice::whereIn('status', PaymentInvoice::PENDING_STATUSES)->count(),
            'completed' => PaymentInvoice::where('status', 'finished')->count(),
            'failed'    => PaymentInvoice::whereIn('status', ['failed', 'expired', 'refunded', 'partially_paid'])->count(),
        ];

        // Deposit analytics
        $analytics = $this->buildAnalytics();

        // Available gateways for filter dropdown
        $availableGateways = PaymentInvoice::select('gateway')
            ->distinct()
            ->pluck('gateway')
            ->filter()
            ->values();

        return Inertia::render('Admin/Payments/Index', [
            'invoices'          => $invoices->through(fn ($inv) => $this->formatInvoice($inv)),
            'counts'            => $counts,
            'filters'           => compact('status', 'search', 'gateway', 'currency', 'dateFrom', 'dateTo'),
            'analytics'         => $analytics,
            'availableGateways' => $availableGateways,
        ]);
    }

    // ── Show (detail with logs) ───────────────────────────────────────────────

    public function show(PaymentInvoice $invoice): InertiaResponse
    {
        $invoice->load(['user:id,name,email', 'depositLogs']);

        return Inertia::render('Admin/Payments/Show', [
            'invoice' => array_merge($this->formatInvoice($invoice), [
                'gateway_payload'      => $invoice->gateway_payload,
                'blockchain_hash'      => $invoice->blockchain_hash,
                'network'              => $invoice->network,
                'amount_received'      => $invoice->amount_received ? (float) $invoice->amount_received : null,
                'usd_value'            => $invoice->usd_value ? (float) $invoice->usd_value : null,
                'ip_address'           => $invoice->ip_address,
                'callback_received_at' => $invoice->callback_received_at?->toISOString(),
                'processed_at'         => $invoice->processed_at?->toISOString(),
                'retry_count'          => $invoice->retry_count,
                'failure_reason'       => $invoice->failure_reason,
                'logs'                 => $invoice->depositLogs->map(fn ($l) => [
                    'id'         => $l->id,
                    'event'      => $l->event,
                    'message'    => $l->message,
                    'metadata'   => $l->metadata,
                    'created_at' => $l->created_at->toISOString(),
                ])->values(),
            ]),
        ]);
    }

    // ── Approve (manual credit) ───────────────────────────────────────────────

    public function approve(Request $request, PaymentInvoice $invoice): RedirectResponse
    {
        if ($invoice->isCredited()) {
            return back()->with('error', 'This deposit has already been credited.');
        }

        $user = $invoice->user()->with('wallet')->first();

        if (!$user || !$user->wallet) {
            return back()->with('error', 'User or wallet not found.');
        }

        try {
            DB::transaction(function () use ($invoice, $user) {
                $wallet        = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                $balanceBefore = (float) $wallet->balance;
                $amount        = (float) $invoice->price_amount;
                $balanceAfter  = round($balanceBefore + $amount, 8);

                $wallet->forceFill([
                    'balance'        => $balanceAfter,
                    'ledger_balance' => $balanceAfter,
                ])->save();

                Transaction::create([
                    'reference'      => Str::uuid(),
                    'user_id'        => $user->id,
                    'wallet_id'      => $wallet->id,
                    'type'           => 'credit',
                    'amount'         => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'status'         => 'completed',
                    'description'    => 'Manual deposit approval — ' . $invoice->reference,
                    'metadata'       => [
                        'gateway'      => $invoice->gateway,
                        'invoice_ref'  => $invoice->reference,
                        'approved_by'  => 'admin',
                        'trigger'      => 'manual_approve',
                    ],
                ]);

                BalanceAdjustment::create([
                    'user_id'        => $user->id,
                    'type'           => 'credit',
                    'amount'         => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'note'           => 'Admin manual deposit approval: ' . $invoice->reference,
                    'admin_user'     => 'admin',
                ]);

                $invoice->update([
                    'status'      => 'finished',
                    'credited_at' => now(),
                    'processed_at'=> now(),
                ]);
            });

            DepositLog::record($invoice->id, 'manual_approve',
                'Admin manually approved deposit',
                ['user_id' => $invoice->user_id, 'amount' => $invoice->price_amount],
            );

            return back()->with('success', 'Deposit approved and $' . number_format($invoice->price_amount, 2) . ' credited.');
        } catch (\Throwable $e) {
            Log::error('[Admin] Payment approve failed', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to approve deposit. Check logs.');
        }
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function reject(Request $request, PaymentInvoice $invoice): RedirectResponse
    {
        if ($invoice->isCredited()) {
            return back()->with('error', 'Cannot reject an already-credited deposit.');
        }

        if (in_array($invoice->status, ['finished', 'failed', 'refunded'], true)) {
            return back()->with('error', 'This deposit is already in a terminal state.');
        }

        $invoice->update(['status' => 'failed']);

        DepositLog::record($invoice->id, 'manual_reject', 'Admin rejected deposit');

        Log::info('[Admin] Deposit rejected', ['invoice' => $invoice->reference]);

        return back()->with('success', 'Deposit rejected.');
    }

    // ── Retry (for finished but uncredited / for failed) ─────────────────────

    public function retry(PaymentInvoice $invoice): RedirectResponse
    {
        if ($invoice->isCredited()) {
            return back()->with('success', 'Deposit is already credited — no action needed.');
        }

        // Allow retry on finished-but-uncredited, or failed (mark it finished first)
        if ($invoice->status === PaymentInvoice::STATUS_FAILED) {
            $invoice->update([
                'status'         => PaymentInvoice::STATUS_FINISHED,
                'failure_reason' => null,
            ]);
        }

        if ($invoice->status !== PaymentInvoice::STATUS_FINISHED) {
            return back()->with('error',
                'Can only retry finished deposits. Current status: ' . $invoice->status
            );
        }

        DepositLog::record($invoice->id, 'retry_scheduled',
            'Admin triggered retry',
            ['retry_count' => $invoice->retry_count],
        );

        // Try synchronous execution first so admin sees immediate result.
        try {
            ProcessDepositJob::dispatchSync($invoice->id, 'admin_retry');
            $invoice->refresh();
            if ($invoice->isCredited()) {
                Log::info('[Admin] Deposit retry credited immediately', ['invoice' => $invoice->reference]);
                return back()->with('success', '$' . number_format($invoice->price_amount, 2) . ' credited successfully.');
            }
        } catch (\Throwable $e) {
            Log::warning('[Admin] Retry dispatchSync failed — queuing async', [
                'invoice' => $invoice->reference,
                'error'   => $e->getMessage(),
            ]);
        }

        ProcessDepositJob::dispatch($invoice->id, 'admin_retry');
        Log::info('[Admin] Deposit retry queued async', ['invoice' => $invoice->reference]);

        return back()->with('success', 'Retry queued — balance will be credited when the worker processes it.');
    }

    // ── Resend callback (legacy alias, kept for backwards compat) ────────────

    public function resendCallback(PaymentInvoice $invoice): RedirectResponse
    {
        return $this->retry($invoice);
    }

    // ── Export CSV ────────────────────────────────────────────────────────────

    public function export(Request $request): Response
    {
        $status   = $request->get('status', '');
        $gateway  = $request->get('gateway', '');
        $currency = $request->get('currency', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo   = $request->get('date_to', '');
        $search   = $request->get('search', '');

        $query = PaymentInvoice::with('user:id,name,email')->latest();

        if ($status) {
            match ($status) {
                'pending'   => $query->whereIn('status', PaymentInvoice::PENDING_STATUSES),
                'completed' => $query->where('status', 'finished'),
                'failed'    => $query->whereIn('status', ['failed', 'expired', 'refunded', 'partially_paid']),
                default     => $query->where('status', $status),
            };
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('blockchain_hash', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u
                      ->where('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        if ($gateway)  $query->where('gateway', $gateway);
        if ($currency) $query->where('pay_currency', strtolower($currency));
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('created_at', '<=', $dateTo);

        $invoices = $query->limit(10000)->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="deposits_' . now()->format('Y-m-d_His') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [
            'ID', 'Reference', 'User Name', 'User Email',
            'Gateway', 'Status', 'Amount (USD)', 'Pay Currency',
            'Network', 'Blockchain Hash', 'Amount Received',
            'Credited', 'Credited At', 'Retry Count',
            'Callback IP', 'Callback At', 'Created At',
        ];

        $csv = implode(',', $columns) . "\n";

        foreach ($invoices as $inv) {
            $csv .= implode(',', [
                $inv->id,
                '"' . $inv->reference . '"',
                '"' . ($inv->user?->name ?? '') . '"',
                '"' . ($inv->user?->email ?? '') . '"',
                $inv->gateway,
                $inv->status,
                number_format((float) $inv->price_amount, 2),
                strtoupper($inv->pay_currency ?? ''),
                $inv->network ?? '',
                '"' . ($inv->blockchain_hash ?? '') . '"',
                $inv->amount_received ? number_format((float) $inv->amount_received, 8) : '',
                $inv->isCredited() ? 'Yes' : 'No',
                $inv->credited_at ? $inv->credited_at->format('Y-m-d H:i:s') : '',
                $inv->retry_count,
                $inv->ip_address ?? '',
                $inv->callback_received_at ? $inv->callback_received_at->format('Y-m-d H:i:s') : '',
                $inv->created_at->format('Y-m-d H:i:s'),
            ]) . "\n";
        }

        return response($csv, 200, $headers);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatInvoice(PaymentInvoice $inv): array
    {
        return [
            'id'                 => $inv->id,
            'reference'          => $inv->reference,
            'user'               => $inv->user
                ? ['id' => $inv->user->id, 'name' => $inv->user->name, 'email' => $inv->user->email]
                : null,
            'price_amount'       => (float) $inv->price_amount,
            'price_currency'     => $inv->price_currency,
            'pay_currency'       => $inv->pay_currency,
            'network'            => $inv->network,
            'blockchain_hash'    => $inv->blockchain_hash,
            'actually_paid'      => $inv->actually_paid ? (float) $inv->actually_paid : null,
            'amount_received'    => $inv->amount_received ? (float) $inv->amount_received : null,
            'pay_address'        => $inv->pay_address,
            'status'             => $inv->status,
            'status_label'       => PaymentInvoice::statusLabel($inv->status),
            'credited_at'        => $inv->credited_at?->toISOString(),
            'callback_received_at' => $inv->callback_received_at?->toISOString(),
            'processed_at'       => $inv->processed_at?->toISOString(),
            'created_at'         => $inv->created_at->toISOString(),
            'gateway'            => $inv->gateway,
            'gateway_invoice_id' => $inv->gateway_invoice_id,
            'gateway_payment_id' => $inv->gateway_payment_id,
            'payment_url'        => $inv->payment_url,
            'retry_count'        => $inv->retry_count,
            'failure_reason'     => $inv->failure_reason,
            'ip_address'         => $inv->ip_address,
            'is_credited'        => $inv->isCredited(),
            'is_finished'        => $inv->isFinished(),
            'can_retry'          => $inv->canBeRetried() || $inv->status === 'failed',
        ];
    }

    private function buildAnalytics(): array
    {
        $total      = PaymentInvoice::count();
        $successful = PaymentInvoice::where('status', 'finished')->count();
        $credited   = PaymentInvoice::whereNotNull('credited_at')->count();
        $pending    = PaymentInvoice::whereIn('status', PaymentInvoice::PENDING_STATUSES)->count();
        $failed     = PaymentInvoice::whereIn('status', ['failed', 'expired'])->count();

        $totalVolume = (float) PaymentInvoice::where('status', 'finished')->sum('price_amount');
        $successRate = $total > 0 ? round($successful / $total * 100, 1) : 0;
        $failedCallbacks = PaymentInvoice::where('status', 'finished')
            ->whereNull('credited_at')
            ->count();

        // Average processing time (seconds from callback_received_at to credited_at)
        $avgProcessSeconds = (float) PaymentInvoice::whereNotNull('credited_at')
            ->whereNotNull('callback_received_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, callback_received_at, credited_at)) as avg_sec')
            ->value('avg_sec');

        // By gateway
        $byGateway = PaymentInvoice::select('gateway', DB::raw('COUNT(*) as count'), DB::raw('SUM(price_amount) as volume'))
            ->where('status', 'finished')
            ->groupBy('gateway')
            ->get()
            ->map(fn ($r) => [
                'gateway' => $r->gateway,
                'count'   => (int) $r->count,
                'volume'  => (float) $r->volume,
            ])
            ->values();

        // By currency
        $byCurrency = PaymentInvoice::select('pay_currency', DB::raw('COUNT(*) as count'))
            ->where('status', 'finished')
            ->whereNotNull('pay_currency')
            ->groupBy('pay_currency')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'currency' => strtoupper($r->pay_currency ?? ''),
                'count'    => (int) $r->count,
            ])
            ->values();

        // Daily for last 14 days
        $daily = collect(range(13, 0))->map(function ($d) {
            $date = now()->subDays($d)->toDateString();
            return [
                'date'       => now()->subDays($d)->format('M d'),
                'total'      => PaymentInvoice::whereDate('created_at', $date)->count(),
                'successful' => PaymentInvoice::where('status', 'finished')->whereDate('created_at', $date)->count(),
                'volume'     => (float) PaymentInvoice::where('status', 'finished')->whereDate('created_at', $date)->sum('price_amount'),
            ];
        })->values();

        // Monthly for last 6 months
        $monthly = collect(range(5, 0))->map(function ($m) {
            $date = now()->subMonths($m);
            return [
                'month'      => $date->format('M Y'),
                'total'      => PaymentInvoice::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'successful' => PaymentInvoice::where('status', 'finished')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'volume'     => (float) PaymentInvoice::where('status', 'finished')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('price_amount'),
            ];
        })->values();

        return compact(
            'total', 'successful', 'credited', 'pending', 'failed',
            'totalVolume', 'successRate', 'failedCallbacks', 'avgProcessSeconds',
            'byGateway', 'byCurrency', 'daily', 'monthly',
        );
    }
}
