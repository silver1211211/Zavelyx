<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'exchange_rate', 'is_active', 'is_default', 'sort_order'];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function active()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('code');
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first();
    }
}
