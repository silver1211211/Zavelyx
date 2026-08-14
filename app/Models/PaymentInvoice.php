<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentInvoice extends Model
{
    protected $fillable = [
        'reference',
        'user_id',
        'gateway',
        'gateway_type',
        'gateway_invoice_id',
        'gateway_payment_id',
        'blockchain_hash',
        'price_amount',
        'price_currency',
        'pay_currency',
        'network',
        'pay_amount',
        'amount_received',
        'usd_value',
        'pay_address',
        'status',
        'payment_url',
        'qr_code_url',
        'expires_at',
        'confirmations',
        'actually_paid',
        'credited_at',
        'gateway_payload',
        'ip_address',
        'callback_received_at',
        'processed_at',
        'retry_count',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'price_amount'        => 'decimal:8',
            'pay_amount'          => 'decimal:18',
            'actually_paid'       => 'decimal:18',
            'amount_received'     => 'decimal:18',
            'usd_value'           => 'decimal:8',
            'credited_at'         => 'datetime',
            'expires_at'          => 'datetime',
            'callback_received_at'=> 'datetime',
            'processed_at'        => 'datetime',
            'gateway_payload'     => 'array',
            'retry_count'         => 'integer',
            'confirmations'       => 'integer',
        ];
    }

    // ── Status constants ──────────────────────────────────────────────────────

    const STATUS_WAITING        = 'waiting';
    const STATUS_CONFIRMING     = 'confirming';
    const STATUS_CONFIRMED      = 'confirmed';
    const STATUS_SENDING        = 'sending';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_FINISHED       = 'finished';
    const STATUS_FAILED         = 'failed';
    const STATUS_REFUNDED       = 'refunded';
    const STATUS_EXPIRED        = 'expired';

    const PENDING_STATUSES = [
        self::STATUS_WAITING,
        self::STATUS_CONFIRMING,
        self::STATUS_CONFIRMED,
        self::STATUS_SENDING,
    ];

    const TERMINAL_STATUSES = [
        self::STATUS_FINISHED,
        self::STATUS_FAILED,
        self::STATUS_REFUNDED,
        self::STATUS_EXPIRED,
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function depositLogs(): HasMany
    {
        return $this->hasMany(DepositLog::class, 'invoice_id')->orderByDesc('created_at');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isCredited(): bool
    {
        return $this->credited_at !== null;
    }

    public function isPending(): bool
    {
        return in_array($this->status, self::PENDING_STATUSES, true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function canBeRetried(): bool
    {
        return $this->isFinished() && !$this->isCredited();
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'waiting'        => 'Waiting for payment',
            'confirming'     => 'Confirming',
            'confirmed'      => 'Confirmed',
            'sending'        => 'Sending',
            'partially_paid' => 'Partially paid',
            'finished'       => 'Finished',
            'failed'         => 'Failed',
            'refunded'       => 'Refunded',
            'expired'        => 'Expired',
            default          => ucfirst($status),
        };
    }
}
