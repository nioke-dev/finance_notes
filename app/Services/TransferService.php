<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class TransferService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function transfer(
        WorkDay $workDay,
        Account $sourceAccount,
        Account $destinationAccount,
        int $amount,
        int $adminFee = 0,
        ?Carbon $transactionTime = null,
        ?string $note = null
    ): Transaction {
        if ($sourceAccount->id === $destinationAccount->id) {
            throw new InvalidArgumentException("Akun sumber dan akun tujuan tidak boleh sama.");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException("Nominal transfer harus lebih besar dari Rp0.");
        }

        if ($adminFee < 0) {
            throw new InvalidArgumentException("Biaya admin tidak boleh negatif.");
        }

        $time = $transactionTime ?? now();
        $description = $note ?? "Transfer dari {$sourceAccount->name} ke {$destinationAccount->name}";

        $entries = [];

        // 1. Kredit akun sumber (nominal + admin)
        $totalDeduction = $amount + $adminFee;
        $entries[] = [
            'account_id' => $sourceAccount->id,
            'entry_type' => EntryType::CREDIT,
            'amount' => $totalDeduction,
        ];

        // 2. Debit akun tujuan (nominal transfer murni)
        $entries[] = [
            'account_id' => $destinationAccount->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => $amount,
        ];

        // 3. Debit akun beban admin (jika ada admin fee > 0)
        if ($adminFee > 0) {
            $adminExpenseAccount = Account::where('name', 'Beban Biaya Admin Transfer')->firstOrFail();
            $entries[] = [
                'account_id' => $adminExpenseAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $adminFee,
            ];
        }

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'transfer',
            description: $description,
            transactionTime: $time,
            entries: $entries,
            isEstimatedTime: false,
            metadata: [
                'from_account_id' => $sourceAccount->id,
                'to_account_id' => $destinationAccount->id,
                'admin_fee' => $adminFee,
            ]
        );
    }
}
