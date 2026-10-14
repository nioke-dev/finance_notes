<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'debt_id',
        'installment_number',
        'due_date',
        'amount_due',
        'amount_paid',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'debt_id' => 'integer',
        'installment_number' => 'integer',
        'due_date' => 'date',
        'amount_due' => 'integer',
        'amount_paid' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
