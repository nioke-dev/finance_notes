<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\WorkDay;

class AutoQrisTipService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function detectAndRecordBalanceIncreases(
        WorkDay $workDay,
        int $actualGoPayBalance,
        int $actualMerchantBalance
    ): array {
        $createdTransactions = [];

        $gopayAccount = Account::where('name', 'GoPay')->firstOrFail();
        $merchantAccount = Account::where('name', 'GoPay Merchant')->firstOrFail();
        $tipIncomeAccount = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->firstOrFail();

        // 1. Cek Kenaikan Saldo GoPay
        if ($actualGoPayBalance > $gopayAccount->current_balance) {
            $diffGoPay = $actualGoPayBalance - $gopayAccount->current_balance;

            $createdTransactions[] = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'income',
                description: 'Pendapatan QRIS / Tip Lapangan (Auto-detected via GoPay)',
                transactionTime: now(),
                entries: [
                    ['account_id' => $gopayAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $diffGoPay],
                    ['account_id' => $tipIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $diffGoPay],
                ],
                isEstimatedTime: false,
                metadata: ['source' => 'gopay_auto_tip', 'amount' => $diffGoPay]
            );
        }

        // 2. Cek Kenaikan Saldo GoPay Merchant
        if ($actualMerchantBalance > $merchantAccount->current_balance) {
            $diffMerchant = $actualMerchantBalance - $merchantAccount->current_balance;

            $createdTransactions[] = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'income',
                description: 'Pendapatan QRIS / Tip Lapangan (Auto-detected via GoPay Merchant)',
                transactionTime: now(),
                entries: [
                    ['account_id' => $merchantAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $diffMerchant],
                    ['account_id' => $tipIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $diffMerchant],
                ],
                isEstimatedTime: false,
                metadata: ['source' => 'gopay_merchant_auto_qris', 'amount' => $diffMerchant]
            );
        }

        return $createdTransactions;
    }
}
