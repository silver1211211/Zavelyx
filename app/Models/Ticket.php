<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'user_id',
        'subject',
        'message',
        'status',
        'priority',
        'category',
        'assigned_to',
        'last_replied_at',
        'pinned',
        'admin_unread',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'admin_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_replied_at'   => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at'       => 'datetime',
            'closed_at'         => 'datetime',
            'admin_viewed_at'   => 'datetime',
            'pinned'            => 'boolean',
            'admin_unread'      => 'boolean',
        ];
    }

    // ── Status workflow ──────────────────────────────────────────────────────
    //
    //  new              → user created ticket, admin hasn't opened it
    //  in_review        → admin opened the ticket (auto-set on first admin view)
    //  waiting_for_user → admin replied, ball is in user's court
    //  user_replied     → user replied after admin — needs admin attention
    //  escalated        → manually escalated by admin
    //  resolved         → admin marked resolved (user can still reopen by replying)
    //  closed           → archived, no further replies allowed
    //
    const STATUSES = ['new', 'in_review', 'waiting_for_user', 'user_replied', 'escalated', 'resolved', 'closed'];
    const PRIORITIES = ['low', 'normal', 'high', 'critical'];
    const CATEGORIES = ['general', 'payment', 'sms', 'otp', 'refund', 'account', 'api', 'technical', 'abuse', 'other'];

    // Statuses that require admin attention (shown in sidebar badge)
    const NEEDS_ATTENTION = ['new', 'user_replied'];
    // Statuses where the ticket is still active/open
    const ACTIVE_STATUSES = ['new', 'in_review', 'waiting_for_user', 'user_replied', 'escalated'];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->orderBy('created_at');
    }

    // ── State helpers ────────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return !in_array($this->status, ['resolved', 'closed']);
    }

    public function isFullyClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function needsAttention(): bool
    {
        return in_array($this->status, self::NEEDS_ATTENTION);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('status', self::NEEDS_ATTENTION);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeAdminUnread($query)
    {
        return $query->where('admin_unread', true);
    }
}
