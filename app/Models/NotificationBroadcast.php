<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationBroadcast extends Model
{
    protected $fillable = [
        'title', 'message', 'type', 'category', 'priority',
        'data', 'action_url', 'action_label', 'open_in_new_tab', 'icon',
        'is_pinned', 'expires_at',
        'target_type', 'target_config',
        'scheduled_at', 'sent_at', 'recipients_count', 'created_by',
    ];

    protected $casts = [
        'data'            => 'array',
        'target_config'   => 'array',
        'is_pinned'       => 'boolean',
        'open_in_new_tab' => 'boolean',
        'expires_at'      => 'datetime',
        'scheduled_at'    => 'datetime',
        'sent_at'         => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null && $this->sent_at === null;
    }
}
