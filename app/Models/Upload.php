<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'file_path',
        'original_name',
        'column_map',
        'status',
        'total_rows',
        'matched_count',
        'unmatched_count',
        'error_msg',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'column_map' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function unmatchedProducts(): HasMany
    {
        return $this->hasMany(UnmatchedProduct::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
