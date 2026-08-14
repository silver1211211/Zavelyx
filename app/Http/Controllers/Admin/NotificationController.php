<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationBroadcast;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $broadcasts = NotificationBroadcast::with('creator:id,name')
            ->latest()
            ->paginate(25);

        $delivered = Notification::count();
        $read      = Notification::where('is_read', true)->count();

        $stats = [
            'total'      => NotificationBroadcast::count(),
            'sent'       => NotificationBroadcast::whereNotNull('sent_at')->count(),
            'scheduled'  => NotificationBroadcast::whereNull('sent_at')->whereNotNull('scheduled_at')->count(),
            'recipients' => $delivered,
            'read'       => $read,
            'unread'     => $delivered - $read,
            'read_rate'  => $delivered > 0 ? round($read / $delivered * 100, 1) : 0,
        ];

        return Inertia::render('Admin/Notifications', [
            'broadcasts' => $broadcasts,
            'stats'      => $stats,
            'types'      => Notification::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'message'         => ['required', 'string'],
            'type'            => ['required', 'string', 'max:64'],
            'category'        => ['required', 'string', 'in:system,transaction,security,promotion'],
            'priority'        => ['required', 'string', 'in:success,warning,error,info,promotion'],
            'action_url'      => ['nullable', 'string', 'max:500'],
            'action_label'    => ['nullable', 'string', 'max:64'],
            'open_in_new_tab' => ['boolean'],
            'is_pinned'       => ['boolean'],
            'expires_at'      => ['nullable', 'date', 'after:now'],
            'target_type'     => ['required', 'string', 'in:all,active,inactive,new_users,role,country,balance_range,specific,date_joined,purchase_activity,verified,unverified,with_balance,without_balance,recent_active'],
            'target_config'   => ['nullable', 'array'],
            'scheduled_at'    => ['nullable', 'date', 'after:now'],
        ]);

        $broadcast = NotificationBroadcast::create([
            ...$validated,
            'created_by' => $request->user()?->id,
        ]);

        if (! $broadcast->scheduled_at) {
            $this->dispatch($broadcast);
        }

        return back()->with('success', 'Notification ' . ($broadcast->scheduled_at ? 'scheduled' : 'sent') . ' successfully.');
    }

    public function update(Request $request, NotificationBroadcast $broadcast): RedirectResponse
    {
        abort_if($broadcast->isSent(), 403, 'Cannot edit a sent notification.');

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'message'         => ['required', 'string'],
            'type'            => ['required', 'string', 'max:64'],
            'category'        => ['required', 'string'],
            'priority'        => ['required', 'string'],
            'action_url'      => ['nullable', 'string', 'max:500'],
            'action_label'    => ['nullable', 'string', 'max:64'],
            'open_in_new_tab' => ['boolean'],
            'is_pinned'       => ['boolean'],
            'expires_at'      => ['nullable', 'date'],
            'target_type'     => ['required', 'string'],
            'target_config'   => ['nullable', 'array'],
            'scheduled_at'    => ['nullable', 'date'],
        ]);

        $broadcast->update($validated);

        return back()->with('success', 'Notification updated.');
    }

    public function destroy(NotificationBroadcast $broadcast): RedirectResponse
    {
        // Delete all per-user notification records globally before removing broadcast
        Notification::where('broadcast_id', $broadcast->id)->delete();
        $broadcast->delete();

        return back()->with('success', 'Notification broadcast and all user records deleted globally.');
    }

    public function send(Request $request, NotificationBroadcast $broadcast): RedirectResponse
    {
        abort_if($broadcast->isSent(), 403, 'Already sent.');
        $this->dispatch($broadcast);
        return back()->with('success', 'Notification dispatched successfully.');
    }

    public function analytics(NotificationBroadcast $broadcast): JsonResponse
    {
        $delivered = Notification::where('broadcast_id', $broadcast->id)->count();
        $read      = Notification::where('broadcast_id', $broadcast->id)->where('is_read', true)->count();
        $targeted  = $broadcast->recipients_count ?? 0;
        $failed    = max(0, $targeted - $delivered);

        $firstOpenAt = Notification::where('broadcast_id', $broadcast->id)
            ->whereNotNull('read_at')
            ->min('read_at');

        $lastInteractionAt = Notification::where('broadcast_id', $broadcast->id)
            ->whereNotNull('read_at')
            ->max('updated_at');

        $readers = Notification::where('broadcast_id', $broadcast->id)
            ->where('is_read', true)
            ->with('user:id,name,email,account_level')
            ->orderByDesc('read_at')
            ->limit(100)
            ->get(['id', 'user_id', 'read_at']);

        $ignored = Notification::where('broadcast_id', $broadcast->id)
            ->where('is_read', false)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'user_id', 'created_at']);

        return response()->json([
            'broadcast' => $broadcast->load('creator:id,name'),
            'stats' => [
                'targeted'          => $targeted,
                'delivered'         => $delivered,
                'failed'            => $failed,
                'read'              => $read,
                'unread'            => $delivered - $read,
                'delivery_rate'     => $targeted > 0 ? round($delivered / $targeted * 100, 1) : 0,
                'read_rate'         => $delivered > 0 ? round($read / $delivered * 100, 1) : 0,
                'first_opened_at'   => $firstOpenAt,
                'last_interaction'  => $lastInteractionAt,
            ],
            'readers' => $readers,
            'ignored' => $ignored,
        ]);
    }

    public function userNotifications(Request $request, User $user): JsonResponse
    {
        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    private function dispatch(NotificationBroadcast $broadcast): void
    {
        $query = User::query();

        switch ($broadcast->target_type) {
            case 'active':
                $query->where('last_active_at', '>=', now()->subDays(30));
                break;

            case 'inactive':
                $query->where(function ($q) {
                    $q->whereNull('last_active_at')
                      ->orWhere('last_active_at', '<', now()->subDays(30));
                });
                break;

            case 'new_users':
                $days = (int) ($broadcast->target_config['days'] ?? 30);
                $query->where('created_at', '>=', now()->subDays($days));
                break;

            case 'verified':
                $query->whereNotNull('email_verified_at');
                break;

            case 'unverified':
                $query->whereNull('email_verified_at');
                break;

            case 'with_balance':
                $query->whereHas('wallet', fn ($q) => $q->where('balance', '>', 0));
                break;

            case 'without_balance':
                $query->where(function ($q) {
                    $q->whereDoesntHave('wallet')
                      ->orWhereHas('wallet', fn ($q2) => $q2->where('balance', '<=', 0));
                });
                break;

            case 'recent_active':
                $days = (int) ($broadcast->target_config['days'] ?? 7);
                $query->where('last_active_at', '>=', now()->subDays($days));
                break;

            case 'role':
                $role = $broadcast->target_config['role'] ?? 'user';
                $query->role($role);
                break;

            case 'country':
                $country = $broadcast->target_config['country'] ?? '';
                $query->where('country', $country);
                break;

            case 'balance_range':
                $min = $broadcast->target_config['min_balance'] ?? 0;
                $max = $broadcast->target_config['max_balance'] ?? PHP_INT_MAX;
                $query->whereHas('wallet', fn ($q) => $q->whereBetween('balance', [$min, $max]));
                break;

            case 'specific':
                $ids = $broadcast->target_config['user_ids'] ?? [];
                $query->whereIn('id', $ids);
                break;

            case 'date_joined':
                $from = $broadcast->target_config['from'] ?? null;
                $to   = $broadcast->target_config['to']   ?? null;
                if ($from) $query->where('created_at', '>=', $from);
                if ($to)   $query->where('created_at', '<=', $to);
                break;

            case 'purchase_activity':
                $minOrders = $broadcast->target_config['min_orders'] ?? 1;
                $query->has('orders', '>=', $minOrders);
                break;
            // 'all' — no filter, send to everyone
        }

        $count = 0;
        $query->select('id')->chunk(500, function ($users) use ($broadcast, &$count) {
            $rows = $users->map(fn ($u) => [
                'user_id'         => $u->id,
                'broadcast_id'    => $broadcast->id,
                'type'            => $broadcast->type,
                'category'        => $broadcast->category,
                'priority'        => $broadcast->priority,
                'title'           => $broadcast->title,
                'message'         => $broadcast->message,
                'data'            => json_encode($broadcast->data),
                'action_url'      => $broadcast->action_url,
                'action_label'    => $broadcast->action_label,
                'open_in_new_tab' => $broadcast->open_in_new_tab,
                'icon'            => $broadcast->icon,
                'is_pinned'       => $broadcast->is_pinned,
                'expires_at'      => $broadcast->expires_at,
                'created_at'      => now(),
                'updated_at'      => now(),
            ])->toArray();

            DB::table('notifications')->insert($rows);
            $count += count($rows);
        });

        $broadcast->update(['sent_at' => now(), 'recipients_count' => $count]);
    }
}
