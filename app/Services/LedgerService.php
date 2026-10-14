<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Exceptions\UnbalancedLedgerException;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * @param array $entries Array of ['account_id' => int, 'entry_type' => EntryType|string, 'amount' => int]
     * @throws UnbalancedLedgerException
     */
    public function recordTransaction(
        WorkDay $workDay,
        string $type,
        string $description,
        Carbon $transactionTime,
        array $entries,
        bool $isEstimatedTime = false,
        ?array $metadata = null
    ): Transaction {
        $totalDebit = 0;
        $totalCredit = 0;

        $normalizedEntries = [];
        foreach ($entries as $entry) {
            $entryType = $entry['entry_type'] instanceof EntryType
                ? $entry['entry_type']
                : EntryType::from($entry['entry_type']);

            $amount = (int) $entry['amount'];

            if ($entryType === EntryType::DEBIT) {
                $totalDebit += $amount;
            } else {
                $totalCredit += $amount;
            }

            $normalizedEntries[] = [
                'account_id' => $entry['account_id'],
                'entry_type' => $entryType,
                'amount' => $amount,
            ];
        }

        if ($totalDebit !== $totalCredit) {
            throw new UnbalancedLedgerException($totalDebit, $totalCredit);
        }

        return DB::transaction(function () use ($workDay, $type, $description, $transactionTime, $normalizedEntries, $isEstimatedTime, $metadata) {
            $transaction = Transaction::create([
                'work_day_id' => $workDay->id,
                'type' => $type,
                'description' => $description,
                'transaction_time' => $transactionTime,
                'is_estimated_time' => $isEstimatedTime,
                'metadata' => $metadata,
            ]);

            foreach ($normalizedEntries as $entry) {
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $entry['account_id'],
                    'entry_type' => $entry['entry_type'],
                    'amount' => $entry['amount'],
                    'created_at' => now(),
                ]);

                $account = Account::lockForUpdate()->find($entry['account_id']);
                if ($account) {
                    $this->updateAccountBalance($account, $entry['entry_type'], $entry['amount']);
                }
            }

            return $transaction->load('ledgerEntries');
        });
    }

    protected function updateAccountBalance(Account $account, EntryType $entryType, int $amount): void
    {
        /**
         * Standar Akuntansi Baku:
         * 1. Akun Aset / Harta (bank, ewallet, cash, receivable):
         *    - Debit = Penambahan saldo (+)
         *    - Kredit = Pengurangan saldo (-)
         * 2. Akun Beban (expense):
         *    - Debit = Penambahan beban (+)
         *    - Kredit = Pengurangan beban (-)
         * 3. Akun Kewajiban / Utang & Pendapatan (debt, income):
         *    - Kredit = Penambahan kewajiban/pendapatan (+)
         *    - Debit = Pengurangan kewajiban/pendapatan (-)
         */
        $accountType = $account->type instanceof AccountType ? $account->type : AccountType::from($account->type);

        if ($accountType->isAsset() || $accountType === AccountType::EXPENSE) {
            $delta = ($entryType === EntryType::DEBIT) ? $amount : -$amount;
        } else {
            // Debt / Income
            $delta = ($entryType === EntryType::CREDIT) ? $amount : -$amount;
        }

        $account->current_balance += $delta;
        $account->save();
    }
}
