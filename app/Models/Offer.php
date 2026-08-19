<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Offer extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_id',
        'upload_id',
        'price',
        'discount',
        'bonus',
        'expires_at',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::invalidatePlatformCache());
        static::deleted(fn () => self::invalidatePlatformCache());
    }

    private static function invalidatePlatformCache(): void
    {
        try {
            Cache::forget('platform_compare_products_cache_v2');
        } catch (\Throwable) {
            // Cache driver failure should never block Offer operations.
        }
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('expires_at', '>', now())
                ->orWhereNull('expires_at');
        });
    }
}
