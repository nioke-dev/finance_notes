<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReversalService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function reverseTransaction(Transaction $originalTransaction, string $reason): Transaction
    {
        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan pembatalan transaksi wajib diisi minimal 5 karakter.");
        }

        if ($originalTransaction->type === 'reversal') {
            throw new InvalidArgumentException("Transaksi pembalik (reversal) tidak dapat dibatalkan kembali.");
        }

        $isAlreadyReversed = Transaction::where('metadata->reversed_transaction_id', $originalTransaction->id)->exists();
        if ($isAlreadyReversed) {
            throw new InvalidArgumentException("Transaksi ini sudah pernah dibatalkan sebelumnya.");
        }

        return DB::transaction(function () use ($originalTransaction, $reason) {
            $reverseEntries = [];

            // Membalik setiap entri debit menjadi kredit, dan kredit menjadi debit
            foreach ($originalTransaction->ledgerEntries as $entry) {
                $invertedType = ($entry->entry_type === EntryType::DEBIT) ? EntryType::CREDIT : EntryType::DEBIT;

                $reverseEntries[] = [
                    'account_id' => $entry->account_id,
                    'entry_type' => $invertedType,
                    'amount' => $entry->amount,
                ];
            }

            // Buat transaksi pembalik via LedgerService
            $reversalTransaction = $this->ledgerService->recordTransaction(
                workDay: $originalTransaction->workDay,
                type: 'reversal',
                description: "Pembalikan Transaksi #{$originalTransaction->id}: {$originalTransaction->description}",
                transactionTime: now(),
                entries: $reverseEntries,
                isEstimatedTime: false,
                metadata: [
                    'reversed_transaction_id' => $originalTransaction->id,
                    'reason' => $reason,
                ]
            );

            // Catat ke audit log
            AuditLog::create([
                'auditable_type' => Transaction::class,
                'auditable_id' => $originalTransaction->id,
                'action' => 'reversal',
                'old_values' => [
                    'transaction_id' => $originalTransaction->id,
                    'type' => $originalTransaction->type,
                    'description' => $originalTransaction->description,
                ],
                'new_values' => [
                    'reversal_transaction_id' => $reversalTransaction->id,
                ],
                'reason' => $reason,
            ]);

            return $reversalTransaction;
        });
    }
}
