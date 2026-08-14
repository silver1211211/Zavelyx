<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'provider_id',
        'name',
        'slug',
        'type',
        'provider_service_code',
        'cost_price',
        'selling_price',
        'min_amount',
        'max_amount',
        'metadata',
        'sms_country',
        'sms_country_name',
        'sms_operator',
        'sms_available_count',
        'number_provider_driver',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price'    => 'decimal:8',
            'selling_price' => 'decimal:8',
            'min_amount'    => 'decimal:2',
            'max_amount'    => 'decimal:2',
            'metadata'      => 'array',
            'sms_available_count' => 'integer',
            'is_active'     => 'boolean',
        ];
    }

    /**
     * Only active services whose provider (if any) is also active.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('services.is_active', true)
            ->where(function (Builder $q): void {
                $q->whereNull('services.provider_id')
                  ->orWhereExists(function ($sub): void {
                      $sub->selectRaw('1')
                          ->from('providers')
                          ->whereColumn('providers.id', 'services.provider_id')
                          ->where('providers.is_active', true);
                  });
            });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
