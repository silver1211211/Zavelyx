<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->active()
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(30);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount'   => $user->unreadNotificationsCount(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->active()
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $user->unreadNotificationsCount(),
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->markAsRead();

        return response()->json(['ok' => true, 'unread_count' => $request->user()->unreadNotificationsCount()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['ok' => true, 'unread_count' => 0]);
    }
}
