<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\SmmProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $status = $request->input('status', '');

        $orders = Order::with([
                'user:id,name,email',
                'service:id,name,cost_price,provider_id',
                'service.provider:id,name',
                'provider:id,name',
            ])
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(function (Order $o) {
                $payload      = $o->payload ?? [];
                $provResponse = $o->provider_response ?? [];
                $quantity     = (int) ($o->quantity ?? 0);

                // Provider cost stored by ProcessOrderJob; fall back to live cost_price
                $cost = isset($provResponse['provider_price'])
                    ? (float) $provResponse['provider_price']
                    : ($quantity > 0 ? round(($quantity / 1000) * (float) ($o->service?->cost_price ?? 0), 8) : null);

                $profit = isset($provResponse['profit'])
                    ? (float) $provResponse['profit']
                    : ($cost !== null ? round((float) $o->amount - $cost, 8) : null);

                // Provider name: prefer direct FK, fall back through service relationship
                $providerName = $o->provider?->name
                    ?? $o->service?->provider?->name;

                return [
                    'id'                => $o->id,
                    'reference'         => $o->reference,
                    'user'              => ['name' => $o->user?->name, 'email' => $o->user?->email],
                    'service'           => $o->service?->name,
                    'amount'            => (float) $o->amount,
                    'status'            => $o->status,
                    'quantity'          => $o->quantity,
                    'link'              => $o->link,
                    'provider_name'     => $providerName,
                    'provider_order_id' => $o->provider_order_id,
                    'cost'              => $cost,
                    'profit'            => $profit,
                    'start_count'       => $o->start_count,
                    'remains'           => $o->remains,
                    'refund_status'     => $o->refund_status,
                    'refund_amount'     => $o->refund_amount !== null ? (float) $o->refund_amount : null,
                    'refunded_at'       => $o->refunded_at?->toISOString(),
                    'last_synced_at'    => $o->last_synced_at?->toISOString(),
                    'created_at'        => $o->created_at->toISOString(),
                    'processed_at'      => $o->processed_at?->toISOString(),
                ];
            });

        $statusCounts = Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return Inertia::render('Admin/Orders', [
            'orders'       => $orders,
            'search'       => $search,
            'statusFilter' => $status,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,processing,completed,partial,canceled,failed'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $order->status;
        $newStatus = $data['status'];

        DB::transaction(function () use ($order, $newStatus, $oldStatus) {
            $order->update([
                'status'       => $newStatus,
                'processed_at' => in_array($newStatus, ['completed', 'canceled', 'partial', 'failed']) ? now() : $order->processed_at,
            ]);

            if (in_array($newStatus, ['canceled', 'partial', 'failed']) && !in_array($oldStatus, ['canceled', 'partial', 'failed'])) {
                $this->issueRefund($order, $newStatus);
            }
        });

        return back()->with('success', "Order #{$order->id} status updated to {$newStatus}.");
    }

    public function syncStatus(Order $order, SmmProviderService $smm): RedirectResponse
    {
        if (!$order->provider_order_id) {
            return back()->withErrors(['order' => 'No provider order ID — cannot sync.']);
        }

        $provider = $order->provider ?? $order->service?->provider;
        if (!$provider) {
            return back()->withErrors(['order' => 'No provider linked to this order.']);
        }

        $res = $smm->checkOrderStatus($provider, $order->provider_order_id);

        if (!$res['success']) {
            return back()->withErrors(['order' => 'Sync failed: ' . $res['message']]);
        }

        $newStatus = $smm->normalizeStatus($res['status']);
        $oldStatus = $order->status;

        DB::transaction(function () use ($order, $newStatus, $oldStatus, $res) {
            $order->update([
                'status'         => $newStatus,
                'start_count'    => $res['start_count'],
                'remains'        => $res['remains'],
                'last_synced_at' => now(),
                'processed_at'   => in_array($newStatus, ['completed', 'canceled', 'partial', 'failed']) ? now() : $order->processed_at,
                'provider_response' => array_merge($order->provider_response ?? [], [
                    'last_raw_status' => $res['status'] ?? $newStatus,
                    'last_synced_at'  => now()->toISOString(),
                    'start_count'     => $res['start_count'],
                    'remains'         => $res['remains'],
                ]),
            ]);

            if (in_array($newStatus, ['canceled', 'partial', 'failed']) && !in_array($oldStatus, ['canceled', 'partial', 'failed'])) {
                $this->issueRefund($order, $newStatus);
            }
        });

        return back()->with('success', "Order synced: {$oldStatus} → {$newStatus}.");
    }

    private function issueRefund(Order $order, string $status): void
    {
        $wallet = $order->user?->wallet;
        if (!$wallet) return;

        // Prevent double-refund via both the new flag and legacy transaction check
        if ($order->refund_status === 'completed') return;
        if (Transaction::where('order_id', $order->id)->where('type', 'credit')->exists()) {
            $order->update(['refund_status' => 'completed']);
            return;
        }

        $refundAmount = 0.0;

        if (in_array($status, ['canceled', 'failed'])) {
            $refundAmount = (float) $order->amount;
        } elseif ($status === 'partial' && $order->quantity > 0 && $order->remains > 0) {
            $unitPrice    = (float) $order->amount / (int) $order->quantity;
            $refundAmount = round($unitPrice * (int) $order->remains, 8);
        }

        if ($refundAmount <= 0) {
            $order->update(['refund_status' => 'completed', 'refund_amount' => 0]);
            return;
        }

        $balanceBefore = (float) $wallet->balance;
        $wallet->increment('balance', $refundAmount);
        $wallet->refresh();

        Transaction::create([
            'reference'      => Str::uuid(),
            'user_id'        => $order->user_id,
            'wallet_id'      => $wallet->id,
            'order_id'       => $order->id,
            'type'           => 'credit',
            'amount'         => $refundAmount,
            'balance_before' => $balanceBefore,
            'balance_after'  => (float) $wallet->balance,
            'status'         => 'completed',
            'description'    => "Admin refund for Order #{$order->id} ({$status})",
        ]);

        $order->update([
            'refund_status' => 'completed',
            'refund_amount' => $refundAmount,
            'refunded_at'   => now(),
        ]);
    }
}
