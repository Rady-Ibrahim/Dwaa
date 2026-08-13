<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRanking extends Model
{
    protected $fillable = [
        'supplier_id',
        'total_items_count',
        'discount_quality_index',
        'indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_quality_index' => 'decimal:2',
            'indexed_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
