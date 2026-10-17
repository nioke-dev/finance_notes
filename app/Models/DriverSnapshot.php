<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_day_id',
        'recorded_at',
        'completed_orders',
        'delivery_fee',
        'hub_income',
        'hub_bonus',
        'customer_tips',
        'shopeepay_balance',
        'gopay_balance',
        'gopay_merchant_balance',
        'cash_balance',
        'notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'completed_orders' => 'integer',
        'delivery_fee' => 'integer',
        'hub_income' => 'integer',
        'hub_bonus' => 'integer',
        'customer_tips' => 'integer',
        'shopeepay_balance' => 'integer',
        'gopay_balance' => 'integer',
        'gopay_merchant_balance' => 'integer',
        'cash_balance' => 'integer',
    ];

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }

    public function totalDriverIncome(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) ($this->delivery_fee ?? 0) + (int) ($this->hub_income ?? 0) + (int) ($this->hub_bonus ?? 0) + (int) ($this->customer_tips ?? 0)
        );
    }

    public function totalDigitalHolding(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->shopeepay_balance + $this->gopay_balance + $this->gopay_merchant_balance
        );
    }

    public function totalWalletHolding(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->shopeepay_balance + $this->gopay_balance + $this->gopay_merchant_balance + $this->cash_balance
        );
    }

    public function merchantSettlementStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->gopay_merchant_balance >= 10000 ? 'ready_to_settle' : 'holding'
        );
    }
}
