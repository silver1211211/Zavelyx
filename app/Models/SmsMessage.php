<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $fillable = [
        'number_order_id',
        'sender',
        'message',
        'code',
        'raw_response',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'received_at'  => 'datetime',
        ];
    }

    public function numberOrder(): BelongsTo
    {
        return $this->belongsTo(NumberOrder::class);
    }
}
