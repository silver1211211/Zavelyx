<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketEvent;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Ticket::with('user:id,name,email')
            ->withCount('replies');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Sort: unread first → pinned → newest activity → newest created
        $tickets = $query
            ->orderByDesc('admin_unread')
            ->orderByDesc('pinned')
            ->orderByDesc('last_replied_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'unread'           => Ticket::where('admin_unread', true)->count(),
            'new'              => Ticket::where('status', 'new')->count(),
            'in_review'        => Ticket::where('status', 'in_review')->count(),
            'waiting_for_user' => Ticket::where('status', 'waiting_for_user')->count(),
            'user_replied'     => Ticket::where('status', 'user_replied')->count(),
            'escalated'        => Ticket::where('status', 'escalated')->count(),
            'resolved'         => Ticket::where('status', 'resolved')->count(),
            'closed'           => Ticket::where('status', 'closed')->count(),
            'total'            => Ticket::count(),
        ];

        return Inertia::render('Admin/Tickets/Index', [
            'tickets'    => $tickets,
            'stats'      => $stats,
            'filters'    => $request->only(['search', 'status', 'priority', 'category']),
            'statuses'   => Ticket::STATUSES,
            'priorities' => Ticket::PRIORITIES,
            'categories' => Ticket::CATEGORIES,
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $ticket->load([
            'user:id,name,email,avatar',
            'assignee:id,name,email',
            'replies.user:id,name,email',
            'replies.attachments',
            'attachments',
            'events',
        ]);

        // Auto-transition: when admin views a "new" or "user_replied" ticket → "in_review"
        if (in_array($ticket->status, ['new', 'user_replied'])) {
            $oldStatus = $ticket->status;
            $ticket->update([
                'status'          => 'in_review',
                'admin_viewed_at' => $ticket->admin_viewed_at ?? now(),
            ]);

            TicketEvent::record(
                $ticket,
                TicketEvent::TYPE_VIEWED,
                'Admin opened and is reviewing this ticket',
                TicketEvent::ACTOR_ADMIN,
                null,
                'Support Team',
                ['old_status' => $oldStatus, 'new_status' => 'in_review'],
            );

            // Reload events after the new one was added
            $ticket->load('events');
        } elseif (!$ticket->admin_viewed_at) {
            $ticket->update(['admin_viewed_at' => now()]);
        }

        $formatAttachments = fn ($collection) => $collection->map(fn ($a) => [
            'id'            => $a->id,
            'original_name' => $a->original_name,
            'url'           => $a->url(),
            'human_size'    => $a->humanSize(),
            'is_image'      => $a->isImage(),
            'mime_type'     => $a->mime_type,
            'is_staff'      => $a->is_staff,
            'ticket_reply_id' => $a->ticket_reply_id,
        ]);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => array_merge($ticket->toArray(), [
                'attachments' => $formatAttachments($ticket->attachments),
                'replies'     => $ticket->replies->map(fn ($r) => array_merge($r->toArray(), [
                    'attachments' => $formatAttachments($r->attachments),
                ])),
                'events' => $ticket->events->map(fn ($e) => [
                    'id'          => $e->id,
                    'type'        => $e->type,
                    'actor_type'  => $e->actor_type,
                    'actor_name'  => $e->actor_name,
                    'description' => $e->description,
                    'metadata'    => $e->metadata,
                    'created_at'  => $e->created_at,
                ]),
            ]),
            'statuses'   => Ticket::STATUSES,
            'priorities' => Ticket::PRIORITIES,
            'categories' => Ticket::CATEGORIES,
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'message'       => ['required', 'string', 'max:10000'],
            'is_internal'   => ['boolean'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,zip'],
        ]);

        $isInternal = (bool) $request->input('is_internal', false);

        $reply = TicketReply::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => null,
            'message'     => $request->message,
            'is_staff'    => true,
            'is_internal' => $isInternal,
        ]);

        if ($request->hasFile('attachments')) {
            Storage::disk('public')->makeDirectory('tickets');
            foreach ($request->file('attachments') as $file) {
                $stored = $file->store('tickets', 'public');
                TicketAttachment::create([
                    'ticket_id'       => $ticket->id,
                    'ticket_reply_id' => $reply->id,
                    'uploaded_by'     => null,
                    'original_name'   => $file->getClientOriginalName(),
                    'stored_name'     => basename($stored),
                    'mime_type'       => $file->getMimeType(),
                    'file_size'       => $file->getSize(),
                    'is_staff'        => true,
                ]);
            }
        }

        if (!$isInternal) {
            // Smart status transition: admin replied → waiting for user
            $ticket->update([
                'status'            => 'waiting_for_user',
                'last_replied_at'   => now(),
                'first_response_at' => $ticket->first_response_at ?? now(),
            ]);

            TicketEvent::record(
                $ticket,
                TicketEvent::TYPE_ADMIN_REPLIED,
                'Support team replied to the ticket',
                TicketEvent::ACTOR_ADMIN,
                null,
                'Support Team',
            );

            // Bell notification → user knows support replied
            $this->notifyUser($ticket, 'support_replied',
                'Support team replied to your ticket',
                "Your ticket \"{$ticket->subject}\" received a reply from our support team.",
            );
        } else {
            TicketEvent::record(
                $ticket,
                TicketEvent::TYPE_INTERNAL_NOTE,
                'Internal note added by Support Team',
                TicketEvent::ACTOR_ADMIN,
                null,
                'Support Team',
            );
        }

        AdminActivityLog::record('ticket_reply', "Replied to ticket #{$ticket->id}", 'Ticket', $ticket->id);

        $message = $isInternal ? 'Internal note added.' : 'Reply sent to user.';

        return back()->with('success', $message);
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:' . implode(',', Ticket::STATUSES)]]);

        $old = $ticket->status;
        $new = $request->status;

        if ($old === $new) {
            return back()->with('success', 'No changes made.');
        }

        $updates = ['status' => $new];

        if ($new === 'resolved' && !$ticket->resolved_at) {
            $updates['resolved_at'] = now();
        }
        if ($new === 'closed' && !$ticket->closed_at) {
            $updates['closed_at'] = now();
        }
        // If reopening from resolved/closed, clear timestamps
        if (in_array($old, ['resolved', 'closed']) && !in_array($new, ['resolved', 'closed'])) {
            $updates['resolved_at'] = null;
            $updates['closed_at']   = null;
        }

        $ticket->update($updates);

        // Determine event type
        $eventType = match ($new) {
            'escalated' => TicketEvent::TYPE_ESCALATED,
            'resolved'  => TicketEvent::TYPE_RESOLVED,
            'closed'    => TicketEvent::TYPE_CLOSED,
            default     => TicketEvent::TYPE_STATUS_CHANGED,
        };

        TicketEvent::record(
            $ticket,
            $eventType,
            "Status changed from \"{$this->statusLabel($old)}\" to \"{$this->statusLabel($new)}\"",
            TicketEvent::ACTOR_ADMIN,
            null,
            'Support Team',
            ['old_status' => $old, 'new_status' => $new],
        );

        AdminActivityLog::record('ticket_status', "Ticket #{$ticket->id}: {$old} → {$new}", 'Ticket', $ticket->id);

        // Notify user when ticket is resolved or closed
        if (in_array($new, ['resolved', 'closed'])) {
            $notifMsg = $new === 'resolved'
                ? "Your ticket \"{$ticket->subject}\" has been marked as resolved. You can reopen it by replying."
                : "Your ticket \"{$ticket->subject}\" has been closed.";

            $this->notifyUser($ticket, "ticket_{$new}", ucfirst($new) . ': ' . $ticket->subject, $notifMsg);
        }

        return back()->with('success', 'Status updated to ' . $this->statusLabel($new) . '.');
    }

    public function updatePriority(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate(['priority' => ['required', 'in:' . implode(',', Ticket::PRIORITIES)]]);

        $old = $ticket->priority;
        $ticket->update(['priority' => $request->priority]);

        TicketEvent::record(
            $ticket,
            TicketEvent::TYPE_PRIORITY_CHANGED,
            "Priority changed from \"{$old}\" to \"{$request->priority}\"",
            TicketEvent::ACTOR_ADMIN,
            null,
            'Support Team',
            ['old_priority' => $old, 'new_priority' => $request->priority],
        );

        AdminActivityLog::record('ticket_priority', "Ticket #{$ticket->id} priority → {$request->priority}", 'Ticket', $ticket->id);

        return back()->with('success', 'Priority updated.');
    }

    public function updateCategory(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate(['category' => ['required', 'in:' . implode(',', Ticket::CATEGORIES)]]);

        $old = $ticket->category;
        $ticket->update(['category' => $request->category]);

        TicketEvent::record(
            $ticket,
            TicketEvent::TYPE_CATEGORY_CHANGED,
            "Category changed from \"{$old}\" to \"{$request->category}\"",
            TicketEvent::ACTOR_ADMIN,
            null,
            'Support Team',
        );

        return back()->with('success', 'Category updated.');
    }

    public function pin(Ticket $ticket): RedirectResponse
    {
        $pinned = !$ticket->pinned;
        $ticket->update(['pinned' => $pinned]);

        TicketEvent::record(
            $ticket,
            TicketEvent::TYPE_PINNED,
            $pinned ? 'Ticket pinned by Support Team' : 'Ticket unpinned by Support Team',
            TicketEvent::ACTOR_ADMIN,
            null,
            'Support Team',
        );

        return back()->with('success', $pinned ? 'Ticket pinned.' : 'Ticket unpinned.');
    }

    public function markRead(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['admin_unread' => false]);

        return back()->with('success', 'Ticket marked as read.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $ref = $ticket->reference;

        // Delete stored attachment files
        $ticket->attachments->each(fn ($a) => Storage::disk('public')->delete('tickets/' . $a->stored_name));
        $ticket->delete();

        AdminActivityLog::record('ticket_delete', "Deleted ticket #{$ref}");

        return redirect()->route('admin.tickets.index')->with('success', "Ticket #{$ref} deleted.");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'new'              => 'New',
            'in_review'        => 'In Review',
            'waiting_for_user' => 'Waiting For User',
            'user_replied'     => 'User Replied',
            'escalated'        => 'Escalated',
            'resolved'         => 'Resolved',
            'closed'           => 'Closed',
            default            => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function notifyUser(Ticket $ticket, string $type, string $title, string $message): void
    {
        Notification::create([
            'user_id'      => $ticket->user_id,
            'type'         => $type,
            'category'     => 'system',
            'priority'     => 'info',
            'title'        => $title,
            'message'      => $message,
            'action_url'   => "/tickets/{$ticket->id}",
            'action_label' => 'View Ticket',
            'icon'         => 'message-square',
        ]);
    }
}
