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
        'damaged_notes_amount',
        'damaged_notes_note',
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
        'damaged_notes_amount' => 'integer',
        'damaged_notes_note' => 'string',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashDenomination $model) {
            $model->total_physical_amount = self::calculateSum($model->toArray());
        });
    }

    public function calculateTotal(): int
    {
        return self::calculateSum($this->toArray());
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

    /**
     * Hitung total lembar & keping uang di dompet
     */
    public function getTotalPiecesAttribute(): int
    {
        return $this->coin_100 + $this->coin_200 + $this->coin_500 + $this->coin_1000
            + $this->bill_1000 + $this->bill_2000 + $this->bill_5000 + $this->bill_10000
            + $this->bill_20000 + $this->bill_50000 + $this->bill_100000;
    }

    /**
     * Dapatkan analisis beban dompet dan rekomendasi pecahan berlebih
     */
    public function getRecommendations(): array
    {
        $recommendations = [];

        // Deteksi lembaran uang kecil yang menumpuk berlebih (dompet terlalu tebal)
        $checks = [
            'bill_5000' => ['nominal' => 5000, 'label' => 'Rp5.000 (Lembar)', 'threshold' => 20],
            'bill_2000' => ['nominal' => 2000, 'label' => 'Rp2.000 (Lembar)', 'threshold' => 30],
            'bill_1000' => ['nominal' => 1000, 'label' => 'Rp1.000 (Lembar)', 'threshold' => 20],
            'coin_1000' => ['nominal' => 1000, 'label' => 'Rp1.000 (Koin)', 'threshold' => 15],
            'coin_500'  => ['nominal' => 500,  'label' => 'Rp500 (Koin)', 'threshold' => 20],
        ];

        foreach ($checks as $field => $info) {
            $count = (int) $this->$field;
            if ($count >= $info['threshold']) {
                $recommendations[] = [
                    'field' => $field,
                    'label' => $info['label'],
                    'count' => $count,
                    'type' => 'excess',
                    'message' => "Beban dompet menumpuk! Anda memiliki {$count} lembar/koin {$info['label']}. Prioritaskan uang ini untuk uang kembalian COD atau bayar bensin/parkir.",
                ];
            }
        }

        // Deteksi jika uang kembalian tipis
        $changeCoins = $this->bill_2000 + $this->bill_5000;
        if ($changeCoins < 5) {
            $recommendations[] = [
                'field' => 'low_change',
                'label' => 'Pecahan Kembalian COD',
                'count' => $changeCoins,
                'type' => 'warning',
                'message' => "Uang pecahan kecil (Rp2.000 & Rp5.000) menipis (sisa {$changeCoins} lembar). Tukar uang atau siapkan pecahan kecil sebelum narik agar tidak susah mencari kembalian.",
            ];
        }

        // 1. Deteksi uang pecahan besar (Rp100k & Rp50k) menumpuk -> Rekomendasi Setor Tunai ATM (CDM)
        $largeBillsAmount = ($this->bill_100000 * 100000) + ($this->bill_50000 * 50000);
        $largeBillsCount = $this->bill_100000 + $this->bill_50000;
        if ($largeBillsAmount >= 1000000) {
            $recommendations[] = [
                'field' => 'large_bills_deposit',
                'label' => 'Setor Tunai ATM (Rp50k/Rp100k)',
                'count' => $largeBillsCount,
                'type' => 'deposit_atm',
                'message' => "Kas fisik besar di dompet mencapai Rp " . number_format($largeBillsAmount, 0, ',', '.') . " ({$largeBillsCount} lembar). Berisiko tinggi di jalan! Mesin ATM Setor Tunai (CDM) hanya menerima Rp50k & Rp100k — disarankan segera setor tunai ke rekening Bank (BCA/Mandiri).",
            ];
        }

        // 2. Deteksi uang pecahan kecil (< Rp50k) menumpuk -> Rekomendasi Top Up ShopeePay via Indomaret/Alfamart
        $smallBillsAmount = ($this->bill_20000 * 20000) + ($this->bill_10000 * 10000)
            + ($this->bill_5000 * 5000) + ($this->bill_2000 * 2000) + ($this->bill_1000 * 1000)
            + ($this->coin_1000 * 1000) + ($this->coin_500 * 500) + ($this->coin_200 * 200) + ($this->coin_100 * 100);
        $smallBillsCount = $this->bill_20000 + $this->bill_10000 + $this->bill_5000 + $this->bill_2000 + $this->bill_1000
            + $this->coin_1000 + $this->coin_500 + $this->coin_200 + $this->coin_100;
        if ($smallBillsAmount >= 200000 || $smallBillsCount >= 25) {
            $recommendations[] = [
                'field' => 'small_bills_retail_deposit',
                'label' => 'Top Up ShopeePay di Indomaret/Alfamart',
                'count' => $smallBillsCount,
                'type' => 'deposit_retail',
                'message' => "Uang pecahan kecil di dompet menumpuk Rp " . number_format($smallBillsAmount, 0, ',', '.') . " ({$smallBillsCount} lembar/koin). Mesin ATM tidak menerima uang di bawah Rp50k. Anda bisa setor fisik via Top Up Tunai ShopeePay di kasir Indomaret/Alfamart (maksimal Rp2.000.000) agar dompet tidak tebal.",
            ];
        }

        // 3. Deteksi lembaran uang rusak / sobek
        if ($this->damaged_notes_amount > 0 || !empty($this->damaged_notes_note)) {
            $recommendations[] = [
                'field' => 'damaged_notes',
                'label' => 'Perhatian Lembaran Uang Rusak / Sobek',
                'count' => 1,
                'type' => 'warning',
                'message' => "Ada uang fisik rusak/sobek senilai Rp " . number_format($this->damaged_notes_amount, 0, ',', '.') . " (" . ($this->damaged_notes_note ?: 'Uang sobek/lusuh') . "). Prioritaskan uang ini untuk dibelanjakan ke SPBU/minimarket atau segera tukarkan ke Bank.",
            ];
        }

        return $recommendations;
    }

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }
}
