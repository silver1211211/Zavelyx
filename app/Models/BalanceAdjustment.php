<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceAdjustment extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'note',
        'admin_user',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:8',
            'balance_before' => 'decimal:8',
            'balance_after'  => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
