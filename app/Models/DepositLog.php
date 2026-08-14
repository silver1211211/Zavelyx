<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'event',
        'message',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'invoice_id');
    }

    public static function record(
        int     $invoiceId,
        string  $event,
        ?string $message = null,
        array   $metadata = [],
    ): self {
        return self::create([
            'invoice_id' => $invoiceId,
            'event'      => $event,
            'message'    => $message,
            'metadata'   => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
