<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    use HasFactory;

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

    /**
     * فلترة سجل البحث للوحة الإدارة (مع تحميل المستخدم والمنتج).
     */
    public function scopeFilter($query, array $filters)
    {
        $query->with(['user:id,name,phone', 'product:id,name_ar,name_en,code'])->latest('created_at');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (! empty($filters['q'])) {
            $query->where('query', 'LIKE', '%'.$filters['q'].'%');
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
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
