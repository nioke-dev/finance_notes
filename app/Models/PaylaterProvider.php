<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaylaterProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'credit_limit',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class, 'platform', 'slug');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getUsedCreditAttribute(): int
    {
        return (int) $this->debts()
            ->where('status', 'active')
            ->get()
            ->sum('remaining_amount');
    }

    public function getAvailableCreditAttribute(): int
    {
        if ($this->credit_limit <= 0) {
            return 0;
        }

        return max(0, $this->credit_limit - $this->used_credit);
    }
}
