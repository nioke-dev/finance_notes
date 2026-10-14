<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class QuickFuelService
{
    public const PRICE_SPBU = 10000;
    public const PRICE_MADURA = 12000;

    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function recordFuel(
        WorkDay $workDay,
        Account $sourceAccount,
        string $vendorType,
        int $amount,
        ?Carbon $transactionTime = null
    ): Transaction {
        $vendor = strtolower(trim($vendorType));
        if (!in_array($vendor, ['spbu', 'madura'])) {
            throw new InvalidArgumentException("Tipe vendor bensin harus 'spbu' atau 'madura'.");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException("Nominal pembelian bensin harus lebih besar dari Rp0.");
        }

        $unitPrice = ($vendor === 'spbu') ? self::PRICE_SPBU : self::PRICE_MADURA;
        $estimatedVolume = round($amount / $unitPrice, 2);

        $fuelExpenseAccount = Account::where('name', 'Beban Bensin Kendaraan')->firstOrFail();

        $vendorLabel = ($vendor === 'spbu') ? 'SPBU' : 'Warung Madura';
        $description = "Beli Bensin ({$vendorLabel}): Rp" . number_format($amount, 0, ',', '.') . " (~{$estimatedVolume} L)";

        $metadata = [
            'type' => 'quick_fuel',
            'vendor_type' => $vendor,
            'unit_price' => $unitPrice,
            'estimated_volume' => $estimatedVolume,
        ];

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $description,
            transactionTime: $transactionTime ?? now(),
            entries: [
                [
                    'account_id' => $sourceAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ],
                [
                    'account_id' => $fuelExpenseAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amount,
                ],
            ],
            isEstimatedTime: false,
            metadata: $metadata
        );
    }
}
