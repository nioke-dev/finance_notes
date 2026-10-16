<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_day_id',
        'type',
        'description',
        'transaction_time',
        'is_estimated_time',
        'metadata',
    ];

    protected $casts = [
        'transaction_time' => 'datetime',
        'is_estimated_time' => 'boolean',
        'metadata' => 'array',
    ];

    public function getAmountAttribute(): int
    {
        if ($this->relationLoaded('ledgerEntries')) {
            $debitEntry = $this->ledgerEntries->first(function ($entry) {
                $val = $entry->entry_type instanceof \App\Enums\EntryType ? $entry->entry_type->value : $entry->entry_type;
                return $val === 'debit';
            });
            return $debitEntry ? (int) $debitEntry->amount : 0;
        }

        return (int) $this->ledgerEntries()->where('entry_type', 'debit')->value('amount');
    }

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Alias for ledgerEntries relationship
     */
    public function entries(): HasMany
    {
        return $this->ledgerEntries();
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('type', 'transfer');
    }
}
