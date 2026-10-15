<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'amount',
        'deduction_day',
        'notes',
        'is_active',
        'last_deducted_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'deduction_day' => 'integer',
        'is_active' => 'boolean',
        'last_deducted_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isDeductedThisMonth(): bool
    {
        if (!$this->last_deducted_at) {
            return false;
        }

        return $this->last_deducted_at->month === now()->month
            && $this->last_deducted_at->year === now()->year;
    }

    public function getDaysUntilDue(): int
    {
        $today = now()->day;
        if ($this->deduction_day >= $today) {
            return $this->deduction_day - $today;
        }

        $daysInCurrentMonth = now()->daysInMonth;
        return ($daysInCurrentMonth - $today) + $this->deduction_day;
    }
}
