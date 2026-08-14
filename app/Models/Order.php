<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'user_id',
        'service_id',
        'provider_id',
        'amount',
        'status',
        'provider_order_id',
        'quantity',
        'link',
        'start_count',
        'remains',
        'refund_status',
        'refund_amount',
        'refunded_at',
        'last_synced_at',
        'payload',
        'provider_response',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:8',
            'refund_amount'  => 'decimal:8',
            'payload'        => 'array',
            'provider_response' => 'array',
            'processed_at'   => 'datetime',
            'refunded_at'    => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isProcessing(): bool { return $this->status === 'processing'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isCanceled(): bool   { return $this->status === 'canceled'; }
    public function isPartial(): bool    { return $this->status === 'partial'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }

    public function isRefundable(): bool
    {
        return in_array($this->status, ['canceled', 'partial', 'failed']);
    }

    public function isRefunded(): bool
    {
        return $this->refund_status === 'completed';
    }

    public function needsRefund(): bool
    {
        return $this->isRefundable() && $this->refund_status === null;
    }
}
