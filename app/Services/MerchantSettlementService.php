<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;

class MerchantSettlementService
{
    public const SETTLEMENT_THRESHOLD = 10000;

    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function processSettlementIfEligible(WorkDay $workDay, int $amountToSettle): ?Transaction
    {
        if ($amountToSettle < self::SETTLEMENT_THRESHOLD) {
            return null; // Belum memenuhi batas minimal Rp10.000
        }

        $merchantAccount = Account::where('name', 'GoPay Merchant')->firstOrFail();
        $gopayAccount = Account::where('name', 'GoPay')->firstOrFail();

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'transfer',
            description: "Auto-Settlement GoPay Merchant ke GoPay (Rp{$amountToSettle})",
            transactionTime: now(),
            entries: [
                [
                    'account_id' => $merchantAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amountToSettle,
                ],
                [
                    'account_id' => $gopayAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amountToSettle,
                ],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'merchant_auto_settlement',
                'amount' => $amountToSettle,
            ]
        );
    }
}
