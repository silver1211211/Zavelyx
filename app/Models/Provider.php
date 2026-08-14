<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'type', 'base_url', 'credentials', 'is_active', 'priority',
        'markup_type', 'markup_value', 'balance', 'last_synced_at',
    ];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'markup_value' => 'decimal:4',
            'last_synced_at' => 'datetime',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
