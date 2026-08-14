<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerificationOtp extends Model
{
    protected $fillable = ['user_id', 'code', 'attempts', 'expires_at', 'last_sent_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= 5;
    }

    public function canResend(): bool
    {
        return is_null($this->last_sent_at) || $this->last_sent_at->addSeconds(60)->isPast();
    }
}
