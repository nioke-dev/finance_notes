<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanDisbursementService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function disburseLoan(
        WorkDay $workDay,
        Debt $debt,
        Account $destinationAccount,
        int $netReceivedAmount,
        int $feeAmount,
        Carbon $disbursedAt,
        int $dueDayOfMonth = 5
    ): Debt {
        if ($netReceivedAmount + $feeAmount !== $debt->total_obligation) {
            throw new InvalidArgumentException(
                "Jumlah dana bersih (Rp{$netReceivedAmount}) + biaya (Rp{$feeAmount}) harus sama dengan total kewajiban utang (Rp{$debt->total_obligation})."
            );
        }

        return DB::transaction(function () use ($workDay, $debt, $destinationAccount, $netReceivedAmount, $feeAmount, $disbursedAt, $dueDayOfMonth) {
            $feeExpenseAccount = Account::firstOrCreate(
                ['name' => 'Beban Biaya Admin & Provisi Pinjaman'],
                [
                    'type' => AccountType::EXPENSE,
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );

            $liabilityAccountName = 'Kewajiban Utang - ' . $debt->name;
            $debtLiabilityAccount = Account::firstOrCreate(
                ['name' => $liabilityAccountName],
                [
                    'type' => AccountType::DEBT,
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );

            $entries = [];

            // 1. Debit rekening bank penerima sebesar dana bersih (Aset bertambah)
            $entries[] = [
                'account_id' => $destinationAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $netReceivedAmount,
            ];

            // 2. Debit biaya provisi jika > 0 (Beban bertambah)
            if ($feeAmount > 0) {
                $entries[] = [
                    'account_id' => $feeExpenseAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $feeAmount,
                ];
            }

            // 3. Kredit akun kewajiban sebesar total utang (Kewajiban bertambah)
            $entries[] = [
                'account_id' => $debtLiabilityAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $debt->total_obligation,
            ];

            $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'debt_disbursement',
                description: "Pencairan Pinjaman: {$debt->name} (Terima Bersih Rp{$netReceivedAmount})",
                transactionTime: $disbursedAt,
                entries: $entries,
                isEstimatedTime: false,
                metadata: [
                    'debt_id' => $debt->id,
                    'net_received' => $netReceivedAmount,
                    'fee' => $feeAmount,
                ]
            );

            // Buat jadwal cicilan otomatis
            $monthlyAmount = intdiv($debt->total_obligation, $debt->tenor_months);
            $remainder = $debt->total_obligation % $debt->tenor_months;

            for ($i = 1; $i <= $debt->tenor_months; $i++) {
                $dueDate = $disbursedAt->copy()->addMonths($i)->setDay(min($dueDayOfMonth, 28));
                
                // Tambahkan sisa pembagian ke cicilan pertama jika ada
                $installmentAmount = ($i === 1) ? $monthlyAmount + $remainder : $monthlyAmount;

                DebtInstallment::create([
                    'debt_id' => $debt->id,
                    'installment_number' => $i,
                    'due_date' => $dueDate,
                    'amount_due' => $installmentAmount,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                ]);
            }

            return $debt->fresh()->load('installments');
        });
    }
}
