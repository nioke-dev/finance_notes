<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkDay extends Model
{
    use HasFactory;

    public const STATUS_NEEDS_RECONCILIATION = 'needs_reconciliation';
    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'work_date',
        'status',
        'opened_at',
        'closed_at',
        'total_orders',
        'online_hours',
        'is_performance_eligible',
        'closing_notes',
    ];

    protected $casts = [
        'work_date' => 'date:Y-m-d',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'total_orders' => 'integer',
        'online_hours' => 'decimal:2',
        'is_performance_eligible' => 'boolean',
    ];

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function needsReconciliation(): bool
    {
        return $this->status === self::STATUS_NEEDS_RECONCILIATION;
    }

    public function scopeUnfinalized(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEEDS_RECONCILIATION);
    }

    public function scopeFinalized(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FINAL);
    }

    public function voiceMemos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VoiceMemo::class);
    }

    public function cashDenomination(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CashDenomination::class);
    }

    public function driverSnapshots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DriverSnapshot::class);
    }

    public function latestSnapshot(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DriverSnapshot::class)->latestOfMany();
    }
}
