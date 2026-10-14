<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialGoal extends Model
{
    use HasFactory;

    public const PRIORITY_PANGAN = 1;
    public const PRIORITY_HYGIENE = 2;
    public const PRIORITY_WORK_ESSENTIAL = 3;
    public const PRIORITY_MAINTENANCE = 4;
    public const PRIORITY_WANTS = 5;

    protected $fillable = [
        'name',
        'category',
        'priority_level',
        'target_amount',
        'allocated_amount',
        'target_date',
        'status',
        'notes',
        'achieved_at',
    ];

    protected $casts = [
        'priority_level' => 'integer',
        'target_amount' => 'integer',
        'allocated_amount' => 'integer',
        'target_date' => 'date',
        'achieved_at' => 'datetime',
    ];

    public function getPriorityLabel(): string
    {
        return $this->priority_label;
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority_level) {
            1 => '🔴 Level 1: Pangan Pokok (Survival)',
            2 => '🟠 Level 2: Kebersihan & Mandi',
            3 => '🟡 Level 3: Nyawa Narik (Oli/Holder)',
            4 => '🔵 Level 4: Servis Motor Berkala',
            5 => '⚪ Level 5: Jajan & Keinginan',
            default => 'Prioritas #' . $this->priority_level,
        };
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority_level) {
            1 => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
            2 => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
            3 => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/40',
            4 => 'bg-blue-500/20 text-blue-300 border-blue-500/40',
            5 => 'bg-slate-700/50 text-slate-300 border-slate-600',
            default => 'bg-slate-800 text-slate-400 border-slate-700',
        };
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->target_amount <= 0) {
            return 100;
        }

        return (int) min(100, round(($this->allocated_amount / $this->target_amount) * 100));
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, $this->target_amount - $this->allocated_amount);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeAchieved(Builder $query): Builder
    {
        return $query->where('status', 'achieved');
    }

    public function scopeSurvival(Builder $query): Builder
    {
        return $query->whereIn('priority_level', [1, 2, 3]);
    }
}
