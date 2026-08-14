<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ticket_id', 'type', 'actor_type', 'actor_id', 'actor_name', 'description', 'metadata', 'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    // Actor type constants
    const ACTOR_USER   = 'user';
    const ACTOR_ADMIN  = 'admin';
    const ACTOR_SYSTEM = 'system';

    // Event type constants
    const TYPE_CREATED         = 'created';
    const TYPE_VIEWED          = 'viewed';
    const TYPE_ADMIN_REPLIED   = 'admin_replied';
    const TYPE_USER_REPLIED    = 'user_replied';
    const TYPE_STATUS_CHANGED  = 'status_changed';
    const TYPE_PRIORITY_CHANGED= 'priority_changed';
    const TYPE_CATEGORY_CHANGED= 'category_changed';
    const TYPE_ESCALATED       = 'escalated';
    const TYPE_RESOLVED        = 'resolved';
    const TYPE_REOPENED        = 'reopened';
    const TYPE_CLOSED          = 'closed';
    const TYPE_PINNED          = 'pinned';
    const TYPE_ASSIGNED        = 'assigned';
    const TYPE_INTERNAL_NOTE   = 'internal_note';

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public static function record(
        Ticket $ticket,
        string $type,
        string $description,
        string $actorType = self::ACTOR_SYSTEM,
        ?int $actorId = null,
        ?string $actorName = null,
        array $metadata = []
    ): self {
        return static::create([
            'ticket_id'  => $ticket->id,
            'type'       => $type,
            'actor_type' => $actorType,
            'actor_id'   => $actorId,
            'actor_name' => $actorName,
            'description'=> $description,
            'metadata'   => empty($metadata) ? null : $metadata,
            'created_at' => now(),
        ]);
    }
}
