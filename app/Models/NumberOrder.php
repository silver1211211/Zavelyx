<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NumberOrder extends Model
{
    // 5SIM status constants
    const STATUS_PENDING   = 'PENDING';
    const STATUS_RECEIVED  = 'RECEIVED';
    const STATUS_FINISHED  = 'FINISHED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_BANNED    = 'BANNED';
    const STATUS_EXPIRED   = 'EXPIRED';
    const STATUS_TIMEOUT   = 'TIMEOUT';

    protected $fillable = [
        'user_id',
        'number_provider_id',
        'wallet_id',
        'activation_id',
        'country',
        'operator',
        'service',
        'phone_number',
        'provider_cost',
        'markup_percent',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'otp_code',
        'sms_text',
        'expires_at',
        'completed_at',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'provider_cost'  => 'decimal:4',
            'markup_percent' => 'decimal:4',
            'amount'         => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after'  => 'decimal:4',
            'expires_at'     => 'datetime',
            'completed_at'   => 'datetime',
            'raw_response'   => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(NumberProvider::class, 'number_provider_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_FINISHED,
            self::STATUS_CANCELLED,
            self::STATUS_BANNED,
            self::STATUS_EXPIRED,
            self::STATUS_TIMEOUT,
        ]);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RECEIVED]);
    }
}
