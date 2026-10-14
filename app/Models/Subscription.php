<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'billing_cycle',
        'amount',
        'due_day_of_month',
        'payment_account_id',
        'is_active',
        'last_paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'due_day_of_month' => 'integer',
        'is_active' => 'boolean',
        'last_paid_at' => 'datetime',
    ];

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isPaidThisMonth(): bool
    {
        if (!$this->last_paid_at) {
            return false;
        }

        return $this->last_paid_at->month === now()->month 
            && $this->last_paid_at->year === now()->year;
    }

    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'work_essential' => '🛵 Kuota / Kerja Narik',
            'living_expense' => '🏠 Kos & Tempat Tinggal',
            'utilities' => '💡 Listrik / Air / Wifi',
            'entertainment' => '🍿 Hiburan / Langganan',
            default => '📦 Rutin Lainnya',
        };
    }

    public function getDaysUntilDue(): int
    {
        $today = now()->day;
        if ($this->due_day_of_month >= $today) {
            return $this->due_day_of_month - $today;
        }

        // Sudah lewat di bulan ini, hitung sampai bulan depan
        $daysInCurrentMonth = now()->daysInMonth;
        return ($daysInCurrentMonth - $today) + $this->due_day_of_month;
    }
}
