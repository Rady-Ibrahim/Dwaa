<?php

namespace App\Models;

use App\Support\SafeCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'name_ar',
        'name_en',
        'code',
        'normalized_name',
        'phonetic_key',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ProductAlias::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
