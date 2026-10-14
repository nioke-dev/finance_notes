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
        ?Carbon $transactionTime = null
    ): Transaction {
        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $parkingExpenseAccount = Account::where('name', 'Beban Parkir')->firstOrFail();

        $description = $restaurantName
            ? "Parkir Motor: {$restaurantName}"
            : "Parkir Motor Lapangan (Quick 1-Tap)";

        $metadata = [
            'type' => 'quick_parking',
            'restaurant_name' => $restaurantName,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $description,
            transactionTime: $transactionTime ?? now(),
            entries: [
                [
                    'account_id' => $cashAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ],
                [
                    'account_id' => $parkingExpenseAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amount,
                ],
            ],
            isEstimatedTime: false,
            metadata: $metadata
        );
    }
}
