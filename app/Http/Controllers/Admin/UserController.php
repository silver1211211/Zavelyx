<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceAdjustment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $users = User::with('wallet:id,user_id,balance,currency,is_active')
            ->withCount('orders')
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn(User $user) => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'wallet'      => $user->wallet ? [
                    'balance'   => (float) $user->wallet->balance,
                    'currency'  => $user->wallet->currency,
                    'is_active' => $user->wallet->is_active,
                ] : null,
                'orders_count'=> $user->orders_count,
                'created_at'  => $user->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Users', [
            'users'  => $users,
            'search' => $search,
        ]);
    }

    public function show(User $user): Response
    {
        $wallet = $user->wallet;

        $orders = $user->orders()
            ->with('service:id,name')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($o) => [
                'id'        => $o->id,
                'reference' => $o->reference,
                'service'   => $o->service?->name,
                'amount'    => (float) $o->amount,
                'status'    => $o->status,
                'quantity'  => $o->quantity,
                'link'      => $o->link,
                'created_at'=> $o->created_at->toISOString(),
            ]);

        $transactions = $user->transactions()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($t) => [
                'id'             => $t->id,
                'reference'      => $t->reference,
                'type'           => $t->type,
                'amount'         => (float) $t->amount,
                'balance_before' => (float) $t->balance_before,
                'balance_after'  => (float) $t->balance_after,
                'description'    => $t->description,
                'status'         => $t->status,
                'created_at'     => $t->created_at->toISOString(),
            ]);

        $adjustments = BalanceAdjustment::where('user_id', $user->id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn($a) => [
                'id'             => $a->id,
                'type'           => $a->type,
                'amount'         => (float) $a->amount,
                'balance_before' => (float) $a->balance_before,
                'balance_after'  => (float) $a->balance_after,
                'note'           => $a->note,
                'admin_user'     => $a->admin_user,
                'created_at'     => $a->created_at->toISOString(),
            ]);

        $stats = [
            'total_orders'    => $user->orders()->count(),
            'completed_orders'=> $user->orders()->where('status', 'completed')->count(),
            'total_spent'     => (float) $user->orders()->sum('amount'),
            'total_refunded'  => (float) $user->transactions()->where('type', 'credit')->sum('amount'),
        ];

        return Inertia::render('Admin/UserDetail', [
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'is_active'  => $user->is_active,
                'created_at' => $user->created_at->toISOString(),
                'wallet'     => $wallet ? [
                    'id'        => $wallet->id,
                    'balance'   => (float) $wallet->balance,
                    'currency'  => $wallet->currency,
                    'is_active' => $wallet->is_active,
                ] : null,
            ],
            'stats'        => $stats,
            'orders'       => $orders,
            'transactions' => $transactions,
            'adjustments'  => $adjustments,
        ]);
    }

    public function adjustBalance(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'type'   => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.000001', 'max:1000000'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        $wallet = $user->wallet;
        if (!$wallet) {
            return back()->withErrors(['amount' => 'User has no wallet.']);
        }

        if ($data['type'] === 'debit' && (float) $wallet->balance < (float) $data['amount']) {
            return back()->withErrors(['amount' => 'Debit amount exceeds user balance.']);
        }

        DB::transaction(function () use ($user, $wallet, $data) {
            $balanceBefore = (float) $wallet->balance;

            if ($data['type'] === 'credit') {
                $wallet->increment('balance', $data['amount']);
            } else {
                $wallet->decrement('balance', $data['amount']);
            }

            $wallet->refresh();

            // Record in transactions table
            Transaction::create([
                'reference'      => Str::uuid(),
                'user_id'        => $user->id,
                'wallet_id'      => $wallet->id,
                'order_id'       => null,
                'type'           => $data['type'],
                'amount'         => $data['amount'],
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'status'         => 'completed',
                'description'    => 'Admin adjustment: ' . ($data['note'] ?? 'Manual balance update'),
            ]);

            // Record in balance_adjustments for history
            BalanceAdjustment::create([
                'user_id'        => $user->id,
                'type'           => $data['type'],
                'amount'         => $data['amount'],
                'balance_before' => $balanceBefore,
                'balance_after'  => (float) $wallet->balance,
                'note'           => $data['note'],
                'admin_user'     => 'admin',
            ]);
        });

        $action = $data['type'] === 'credit' ? 'added to' : 'removed from';
        return back()->with('success', "₦{$data['amount']} {$action} {$user->name}'s wallet.");
    }

    public function freeze(User $user): RedirectResponse
    {
        $user->update(['is_active' => false]);

        if ($user->wallet) {
            $user->wallet->update(['is_active' => false]);
        }

        return back()->with('success', "{$user->name}'s account has been frozen.");
    }

    public function unfreeze(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        if ($user->wallet) {
            $user->wallet->update(['is_active' => true]);
        }

        return back()->with('success', "{$user->name}'s account has been activated.");
    }
}
