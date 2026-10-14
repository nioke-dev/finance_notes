<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;

class QuickParkingService
{
    public const DEFAULT_PARKING_FEE = 1000;

    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function recordParking(
        WorkDay $workDay,
        int $amount = self::DEFAULT_PARKING_FEE,
        ?string $restaurantName = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?Carbon $transactionTime = null,
        bool $isCoveredByResto = false
    ): Transaction {
        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $parkingExpenseAccount = Account::where('name', 'Beban Parkir')->firstOrFail();

        // Jika ditanggung resto, pengeluaran kas driver = Rp0
        $effectiveCashDeduction = $isCoveredByResto ? 0 : $amount;

        $description = $restaurantName
            ? ($isCoveredByResto ? "Parkir Motor (Gratis/Ditanggung Resto): {$restaurantName}" : "Parkir Motor: {$restaurantName}")
            : ($isCoveredByResto ? "Parkir Motor (Gratis/Ditanggung Resto)" : "Parkir Motor Lapangan (Quick 1-Tap)");

        $metadata = [
            'type' => 'quick_parking',
            'restaurant_name' => $restaurantName,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_covered_by_resto' => $isCoveredByResto,
            'original_fee' => $amount,
        ];

        $entries = [];
        if ($effectiveCashDeduction > 0) {
            $entries[] = [
                'account_id' => $cashAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $effectiveCashDeduction,
            ];
            $entries[] = [
                'account_id' => $parkingExpenseAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $effectiveCashDeduction,
            ];
        } else {
            // Entri balancing nol atau penanda catatan operasional
            $entries[] = [
                'account_id' => $cashAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => 0,
            ];
            $entries[] = [
                'account_id' => $parkingExpenseAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => 0,
            ];
        }

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $description,
            transactionTime: $transactionTime ?? now(),
            entries: $entries,
            isEstimatedTime: false,
            metadata: $metadata
        );
    }
}
