<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FriendDebtSettlementService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function settleFriendDebt(
        WorkDay $workDay,
        Debt $debt,
        Account $receivingAccount,
        int $receivedAmount,
        bool $forgiveRemainder = false
    ): Transaction {
        if ($debt->isPaidOff()) {
            throw new InvalidArgumentException("Utang ini sudah lunas sebelumnya.");
        }

        if ($receivedAmount <= 0) {
            throw new InvalidArgumentException("Nominal uang yang diterima harus lebih besar dari Rp0.");
        }

        if ($receivedAmount > $debt->total_obligation) {
            throw new InvalidArgumentException("Uang diterima melebihi total kewajiban utang.");
        }

        return DB::transaction(function () use ($workDay, $debt, $receivingAccount, $receivedAmount, $forgiveRemainder) {
            $charityExpenseAccount = Account::firstOrCreate(
                ['name' => 'Beban Sedekah & Bantuan Pribadi'],
                [
                    'type' => AccountType::EXPENSE,
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );

            $receivableAccountName = 'Piutang Teman - ' . ($debt->borrower_name ?: $debt->name);
            $receivableAccount = Account::firstOrCreate(
                ['name' => $receivableAccountName],
                [
                    'type' => AccountType::RECEIVABLE,
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );

            $remainder = $debt->total_obligation - $receivedAmount;
            $isFullySettled = ($remainder === 0) || $forgiveRemainder;

            $entries = [];

            // 1. Debit akun penerima sebesar uang riil masuk (Kas/Bank bertambah)
            $entries[] = [
                'account_id' => $receivingAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $receivedAmount,
            ];

            // 2. Jika diikhlaskan dan ada sisa, debit akun beban sedekah (Beban sedekah bertambah)
            if ($forgiveRemainder && $remainder > 0) {
                $entries[] = [
                    'account_id' => $charityExpenseAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $remainder,
                ];
            }

            // 3. Kredit akun piutang sebesar total yang ditutup (Piutang berkurang)
            $creditedAmount = $forgiveRemainder ? $debt->total_obligation : $receivedAmount;
            $entries[] = [
                'account_id' => $receivableAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $creditedAmount,
            ];

            $transaction = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'income',
                description: "Pelunasan Utang Teman: " . ($debt->borrower_name ?: $debt->name) . " (Terima Rp{$receivedAmount}" . ($forgiveRemainder && $remainder > 0 ? ", Sedekah Rp{$remainder}" : "") . ")",
                transactionTime: now(),
                entries: $entries,
                isEstimatedTime: false,
                metadata: [
                    'debt_id' => $debt->id,
                    'borrower_name' => $debt->borrower_name,
                    'received_amount' => $receivedAmount,
                    'remainder' => $remainder,
                    'forgiven_as_charity' => $forgiveRemainder,
                ]
            );

            if ($isFullySettled) {
                $debt->update(['status' => 'paid_off']);
            }

            return $transaction;
        });
    }
}
