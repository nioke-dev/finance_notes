<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveCodOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_day_id',
        'order_code',
        'merchant_name',
        'bill_amount',
        'rounded_bill_amount',
        'status',
        'accepted_at',
        'accepted_latitude',
        'accepted_longitude',
        'initial_transaction_id',
        'payment_method',
        'customer_gave_amount',
        'change_returned',
        'received_amount',
        'tip_amount',
        'parking_reimbursed',
        'settlement_account_id',
        'delivered_at',
        'delivered_latitude',
        'delivered_longitude',
        'completion_transaction_id',
        'notes',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'delivered_at' => 'datetime',
        'accepted_latitude' => 'float',
        'accepted_longitude' => 'float',
        'delivered_latitude' => 'float',
        'delivered_longitude' => 'float',
        'bill_amount' => 'integer',
        'rounded_bill_amount' => 'integer',
        'customer_gave_amount' => 'integer',
        'change_returned' => 'integer',
        'received_amount' => 'integer',
        'tip_amount' => 'integer',
        'parking_reimbursed' => 'integer',
    ];

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }

    public function initialTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'initial_transaction_id');
    }

    public function completionTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'completion_transaction_id');
    }

    public function settlementAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'settlement_account_id');
    }

    public function scopeOnDelivery(Builder $query): Builder
    {
        return $query->where('status', 'on_delivery');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Hitung pembulatan ke atas ala driver lapangan (kelipatan Rp1.000 terdekat).
     * Contoh: Rp51.700 -> Rp52.000, Rp34.200 -> Rp35.000, Rp50.000 -> Rp50.000.
     */
    public static function calculateRounding(int $billAmount): int
    {
        if ($billAmount <= 0) {
            return 0;
        }

        return $billAmount % 1000 === 0 
            ? $billAmount 
            : (int) (ceil($billAmount / 1000) * 1000);
    }
}
