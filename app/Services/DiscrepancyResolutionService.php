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
}
