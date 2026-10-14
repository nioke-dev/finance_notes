<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DiscrepancyResolutionService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function addForgottenExpense(
        WorkDay $pastWorkDay,
        Account $sourceAccount,
        Account $expenseAccount,
        int $amount,
        string $description,
        string $reason,
        bool $usePlaceholderTime = true,
        ?Carbon $actualTime = null
    ): Transaction {
        if ($pastWorkDay->isFinal()) {
            throw new InvalidArgumentException("Hari kerja ini sudah final, tidak dapat menambahkan transaksi secara langsung.");
        }

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan penambahan transaksi terlupa wajib diisi minimal 5 karakter.");
        }

        $transactionTime = $usePlaceholderTime
            ? $pastWorkDay->work_date->copy()->setTime(23, 59, 0)
            : ($actualTime ?? now());

        $transaction = $this->ledgerService->recordTransaction(
            workDay: $pastWorkDay,
            type: 'expense',
            description: "{$description} (Input Terlambat)",
            transactionTime: $transactionTime,
            entries: [
                ['account_id' => $sourceAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $amount],
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $amount],
            ],
            isEstimatedTime: $usePlaceholderTime,
            metadata: [
                'type' => 'late_entry_resolution',
                'reason' => $reason,
                'resolved_at' => now()->toISOString(),
            ]
        );

        AuditLog::create([
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'action' => 'create',
            'old_values' => null,
            'new_values' => ['amount' => $amount, 'description' => $description],
            'reason' => "Input transaksi terlupa ke hari lampau: {$reason}",
        ]);

        return $transaction;
    }

    /**
     * Catat penyesuaian selisih kas fisik yang tidak terlacak (hilang atau lebih)
     * agar rekonsiliasi kas menjadi seimbang sempurna (Rp0) dan dapat difinalisasi.
     */
    public function adjustUnresolvedCashDiscrepancy(
        WorkDay $workDay,
        Account $cashAccount,
        int $discrepancy,
        string $reason
    ): Transaction {
        if ($workDay->isFinal()) {
            throw new InvalidArgumentException("Hari kerja ini sudah difinalisasi.");
        }

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan penyesuaian selisih kas wajib diisi minimal 5 karakter.");
        }

        if ($discrepancy === 0) {
            throw new InvalidArgumentException("Tidak ada selisih kas yang perlu disesuaikan.");
        }

        $isShortage = $discrepancy < 0;
        $amount = abs($discrepancy);

        if ($isShortage) {
            // Kas Fisik Kurang (Uang Hilang / Tercecer / Lupa Minta Kembalian)
            $expenseAccount = Account::firstOrCreate(
                ['name' => 'Beban Selisih Kas (Hilang/Kurang)'],
                ['type' => \App\Enums\AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
            );

            $entries = [
                ['account_id' => $cashAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $amount],
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $amount],
            ];
            $desc = "Penyesuaian Selisih Kas Kurang (Uang Hilang/Kurang): Rp " . number_format($amount, 0, ',', '.');
        } else {
            // Kas Fisik Lebih (Surplus Tak Terlacak / Kembalian Lebih)
            $incomeAccount = Account::firstOrCreate(
                ['name' => 'Pendapatan Selisih Kas (Surplus Tak Terlacak)'],
                ['type' => \App\Enums\AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
            );

            $entries = [
                ['account_id' => $cashAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $amount],
                ['account_id' => $incomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $amount],
            ];
            $desc = "Penyesuaian Selisih Kas Lebih (Surplus Tak Terlacak): Rp " . number_format($amount, 0, ',', '.');
        }

        $transaction = $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'discrepancy_adjustment',
            description: $desc,
            transactionTime: $workDay->work_date->copy()->setTime(23, 59, 59),
            entries: $entries,
            isEstimatedTime: true,
            metadata: [
                'type' => 'unresolved_discrepancy_adjustment',
                'adjustment_type' => $isShortage ? 'shortage' : 'surplus',
                'amount' => $amount,
                'discrepancy_raw' => $discrepancy,
                'reason' => $reason,
                'adjusted_at' => now()->toIso8601String(),
            ]
        );

        AuditLog::create([
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'action' => 'adjust_discrepancy',
            'old_values' => ['discrepancy' => $discrepancy],
            'new_values' => ['discrepancy' => 0, 'amount' => $amount, 'reason' => $reason],
            'reason' => "Penyesuaian selisih kas fisik closing: {$reason}",
        ]);

        return $transaction;
    }
}
