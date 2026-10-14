<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDenomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_day_id',
        'coin_100',
        'coin_200',
        'coin_500',
        'coin_1000',
        'bill_1000',
        'bill_2000',
        'bill_5000',
        'bill_10000',
        'bill_20000',
        'bill_50000',
        'bill_100000',
        'total_physical_amount',
    ];

    protected $casts = [
        'coin_100' => 'integer',
        'coin_200' => 'integer',
        'coin_500' => 'integer',
        'coin_1000' => 'integer',
        'bill_1000' => 'integer',
        'bill_2000' => 'integer',
        'bill_5000' => 'integer',
        'bill_10000' => 'integer',
        'bill_20000' => 'integer',
        'bill_50000' => 'integer',
        'bill_100000' => 'integer',
        'total_physical_amount' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashDenomination $model) {
            $model->total_physical_amount = self::calculateSum($model->toArray());
        });
    }

    public static function calculateSum(array $data): int
    {
        return (100 * (int) ($data['coin_100'] ?? 0))
            + (200 * (int) ($data['coin_200'] ?? 0))
            + (500 * (int) ($data['coin_500'] ?? 0))
            + (1000 * (int) ($data['coin_1000'] ?? 0))
            + (1000 * (int) ($data['bill_1000'] ?? 0))
            + (2000 * (int) ($data['bill_2000'] ?? 0))
            + (5000 * (int) ($data['bill_5000'] ?? 0))
            + (10000 * (int) ($data['bill_10000'] ?? 0))
            + (20000 * (int) ($data['bill_20000'] ?? 0))
            + (50000 * (int) ($data['bill_50000'] ?? 0))
            + (100000 * (int) ($data['bill_100000'] ?? 0));
    }

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }
}
