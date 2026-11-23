<?php

namespace App\Models;

use App\Support\SafeCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::invalidatePlatformCache());
        static::deleted(fn () => self::invalidatePlatformCache());
    }

    private static function invalidatePlatformCache(): void
    {
        SafeCache::forget('platform_compare_products_cache_v3');
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
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
        return $query->where('is_active', true)
            ->where(function (Builder $query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            });
    }
}
