<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    public const SOURCE_TEXT = 'text';

    public const SOURCE_EXCEL_ROW = 'excel_row';

    public const SOURCE_EXCEL_BULK = 'excel_bulk';

    protected $fillable = [
        'user_id',
        'source',
        'bulk_session_id',
        'query',
        'product_id',
        'results_count',
        'had_offers',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'had_offers' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * سجلات تُستخدم في تجميعات «أكثر استعلامات» و«بدون نتائج» (تستثني ملخص رفع الإكسل).
     */
    public function scopeForQueryAggregates($query)
    {
        return $query->whereIn('source', [self::SOURCE_TEXT, self::SOURCE_EXCEL_ROW]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
