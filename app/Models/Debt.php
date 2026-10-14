<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'borrower_name',
        'principal_amount',
        'fee_amount',
        'total_obligation',
        'tenor_months',
        'status',
        'started_at',
    ];

    protected $casts = [
        'principal_amount' => 'integer',
        'fee_amount' => 'integer',
        'total_obligation' => 'integer',
        'tenor_months' => 'integer',
        'started_at' => 'date',
    ];

    public function isPaidOff(): bool
    {
        return $this->status === 'paid_off';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePaidOff(Builder $query): Builder
    {
        return $query->where('status', 'paid_off');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(DebtInstallment::class);
    }

    public function getRemainingAmountAttribute(): int
    {
        if ($this->installments()->exists()) {
            $paid = (int) $this->installments()->where('status', 'paid')->sum('amount_paid');
            return max(0, $this->total_obligation - $paid);
        }

        return $this->isPaidOff() ? 0 : (int) $this->total_obligation;
    }
}
