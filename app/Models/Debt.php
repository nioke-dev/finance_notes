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

    public const PLATFORMS_PAYLATER = [
        'spaylater',
        'gopay_later',
        'tiktok_paylater',
        'kredivo_paylater',
        'akulaku_paylater',
        'indodana_paylater',
        'other_paylater',
    ];
    public const PLATFORMS_CASH_LOAN = [
        'spinjam',
        'seabank_pinjam',
        'gopay_pinjam',
        'superbank_pinjam',
        'kredivo_pinjam',
        'bank',
        'other',
    ];

    public function isPaidOff(): bool
    {
        return $this->status === 'paid_off';
    }

    public function isPayLater(): bool
    {
        return (in_array($this->platform, self::PLATFORMS_PAYLATER) || str_contains($this->platform, 'later') || str_contains($this->platform, 'cicil'))
            && ($this->borrower_name === null || $this->borrower_name === 'Self');
    }

    public function isCashLoan(): bool
    {
        return !$this->isPayLater() && !$this->isFriendLoan();
    }

    public function isFriendLoan(): bool
    {
        return $this->platform === 'person' 
            || (!empty($this->borrower_name) && $this->borrower_name !== 'Self');
    }

    public function getPlatformLabel(): string
    {
        return match($this->platform) {
            'spaylater' => 'Shopee PayLater',
            'gopay_later' => 'GoPay Later',
            'tiktok_paylater' => 'TikTok PayLater',
            'kredivo_paylater' => 'Kredivo PayLater',
            'akulaku_paylater' => 'Akulaku PayLater',
            'indodana_paylater' => 'Indodana PayLater',
            'spinjam' => 'Shopee Pinjam (SPinjam)',
            'seabank_pinjam' => 'SeaBank Pinjam',
            'gopay_pinjam' => 'GoPay Pinjam',
            'superbank_pinjam' => 'Superbank Pinjam',
            'kredivo_pinjam' => 'Kredivo Pinjam',
            'bank' => 'Pinjaman Bank',
            'person' => 'Pinjaman Teman',
            default => str_ireplace(['paylater', 'pinjam'], ['PayLater', 'Pinjam'], ucwords(str_replace('_', ' ', $this->platform ?? 'Lainnya'))),
        };
    }

    public function getCategoryLabel(): string
    {
        if ($this->isFriendLoan()) {
            return 'Piutang Teman';
        }
        if ($this->isPayLater()) {
            return 'PayLater (Belanja Tempo)';
        }
        return 'Pinjaman Tunai (KTA)';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePaidOff(Builder $query): Builder
    {
        return $query->where('status', 'paid_off');
    }

    public function scopePayLater(Builder $query): Builder
    {
        return $query->whereIn('platform', self::PLATFORMS_PAYLATER)
            ->where(function ($q) {
                $q->whereNull('borrower_name')->orWhere('borrower_name', 'Self');
            });
    }

    public function scopeCashLoan(Builder $query): Builder
    {
        return $query->whereIn('platform', self::PLATFORMS_CASH_LOAN)
            ->where(function ($q) {
                $q->whereNull('borrower_name')->orWhere('borrower_name', 'Self');
            });
    }

    public function scopeFriendLoan(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('platform', 'person')
              ->orWhere(function ($sub) {
                  $sub->whereNotNull('borrower_name')->where('borrower_name', '!=', 'Self');
              });
        });
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
