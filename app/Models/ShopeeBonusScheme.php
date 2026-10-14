<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopeeBonusScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'tier_level',
        'min_orders',
        'max_orders',
        'rate_per_order',
        'is_active',
    ];

    protected $casts = [
        'tier_level' => 'integer',
        'min_orders' => 'integer',
        'max_orders' => 'integer',
        'rate_per_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
