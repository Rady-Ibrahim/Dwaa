<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'phone1',
        'phone2',
        'area',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
