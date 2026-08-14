<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketEvent;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $query = $request->user()->tickets()->withCount('replies');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Pinned tickets float to the top, then by latest
        $tickets = $query->orderByDesc('pinned')->latest()->paginate(15)->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject'       => ['required', 'string', 'max:255'],
            'message'       => ['required', 'string', 'max:5000'],
            'priority'      => ['required', 'in:low,normal,high'],
            'category'      => ['nullable', 'in:' . implode(',', Ticket::CATEGORIES)],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,zip'],
        ]);

        $ticket = Ticket::create([
            'reference'    => Str::uuid(),
            'user_id'      => $request->user()->id,
            'subject'      => $validated['subject'],
            'message'      => $validated['message'],
            'priority'     => $validated['priority'],
            'category'     => $validated['category'] ?? 'general',
            'status'       => 'new',
            'admin_unread' => true,
        ]);

        $this->storeAttachments($request, $ticket, null, false);

        TicketEvent::record(
            $ticket,
            TicketEvent::TYPE_CREATED,
            "Ticket submitted by {$request->user()->name}",
            TicketEvent::ACTOR_USER,
            $request->user()->id,
            $request->user()->name,
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket submitted! Our support team will review it shortly.');
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        $ticket->load([
            'replies.user:id,name',
            'replies.attachments',
            'attachments',
            'events',
        ]);

        $formatAttachments = fn ($collection) => $collection->map(fn ($a) => [
            'id'            => $a->id,
            'original_name' => $a->original_name,
            'url'           => $a->url(),
            'human_size'    => $a->humanSize(),
            'is_image'      => $a->isImage(),
            'mime_type'     => $a->mime_type,
            'ticket_reply_id' => $a->ticket_reply_id,
        ]);

        return Inertia::render('Tickets/Show', [
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
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);
        abort_if($ticket->isFullyClosed(), 403, 'This ticket is closed and cannot receive new replies.');

        $request->validate([
            'message'       => ['required', 'string', 'max:5000'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf,txt,zip'],
        ]);

        $wasResolved = $ticket->isResolved();
        $oldStatus   = $ticket->status;

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $request->user()->id,
            'message'   => $request->message,
            'is_staff'  => false,
        ]);

        $this->storeAttachments($request, $ticket, $reply, false);

        // Smart status transition — surfaces to admin as unread
        $ticket->update([
            'status'          => 'user_replied',
            'last_replied_at' => now(),
            'admin_unread'    => true,
        ]);

        // Record timeline event
        if ($wasResolved) {
            TicketEvent::record(
                $ticket,
                TicketEvent::TYPE_REOPENED,
                "{$request->user()->name} replied — ticket reopened automatically",
                TicketEvent::ACTOR_USER,
                $request->user()->id,
                $request->user()->name,
                ['old_status' => $oldStatus, 'new_status' => 'user_replied'],
            );
        } else {
            TicketEvent::record(
                $ticket,
                TicketEvent::TYPE_USER_REPLIED,
                "{$request->user()->name} replied to the ticket",
                TicketEvent::ACTOR_USER,
                $request->user()->id,
                $request->user()->name,
            );
        }

        $message = $wasResolved
            ? 'Your reply reopened the ticket. Our team will respond shortly.'
            : 'Reply sent successfully.';

        return back()->with('success', $message);
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);
        abort_unless($ticket->isOpen(), 403);

        $oldStatus = $ticket->status;
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        TicketEvent::record(
            $ticket,
            TicketEvent::TYPE_CLOSED,
            "Ticket closed by {$request->user()->name}",
            TicketEvent::ACTOR_USER,
            $request->user()->id,
            $request->user()->name,
            ['old_status' => $oldStatus],
        );

        return back()->with('success', 'Ticket closed.');
    }

    public function reopen(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        $oldStatus = $ticket->status;
        $ticket->update(['status' => 'user_replied', 'closed_at' => null]);

        TicketEvent::record(
            $ticket,
            TicketEvent::TYPE_REOPENED,
            "Ticket reopened by {$request->user()->name}",
            TicketEvent::ACTOR_USER,
            $request->user()->id,
            $request->user()->name,
            ['old_status' => $oldStatus, 'new_status' => 'user_replied'],
        );

        return back()->with('success', 'Ticket reopened. Our team will respond shortly.');
    }

    private function storeAttachments(Request $request, Ticket $ticket, ?TicketReply $reply, bool $isStaff): void
    {
        if (!$request->hasFile('attachments')) return;

        Storage::disk('public')->makeDirectory('tickets');

        foreach ($request->file('attachments') as $file) {
            $stored = $file->store('tickets', 'public');

            TicketAttachment::create([
                'ticket_id'       => $ticket->id,
                'ticket_reply_id' => $reply?->id,
                'uploaded_by'     => $request->user()?->id,
                'original_name'   => $file->getClientOriginalName(),
                'stored_name'     => basename($stored),
                'mime_type'       => $file->getMimeType(),
                'file_size'       => $file->getSize(),
                'is_staff'        => $isStaff,
            ]);
        }
    }
}
