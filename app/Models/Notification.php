<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'broadcast_id', 'type', 'category', 'priority', 'title', 'message',
        'data', 'action_url', 'action_label', 'open_in_new_tab', 'icon',
        'is_read', 'read_at', 'is_pinned', 'expires_at',
    ];

    protected $casts = [
        'data'            => 'array',
        'is_read'         => 'boolean',
        'is_pinned'       => 'boolean',
        'open_in_new_tab' => 'boolean',
        'read_at'         => 'datetime',
        'expires_at'      => 'datetime',
    ];

    // Priority → colour token mapping (consumed by frontend)
    const PRIORITY_COLORS = [
        'success'   => 'emerald',
        'warning'   => 'amber',
        'error'     => 'rose',
        'info'      => 'sky',
        'promotion' => 'violet',
    ];

    const TYPES = [
        'welcome', 'deposit_success', 'deposit_failed', 'number_purchased',
        'otp_received', 'refund_processed', 'promotional', 'maintenance',
        'security_alert', 'new_feature', 'verification_reminder',
        'inactive_reminder', 'bonus_reward', 'balance_low', 'admin_custom',
        'flash_sale', 'service_outage', 'provider_maintenance',
        'cashback', 'loyalty_vip',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }
}
