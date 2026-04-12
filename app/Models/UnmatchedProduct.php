<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnmatchedProduct extends Model
{
    protected $fillable = [
        'upload_id',
        'raw_name',
        'normalized_name',
        'status',
        'resolved_product_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function resolvedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'resolved_product_id');
    }
}
