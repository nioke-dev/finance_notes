<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\DebtInstallment;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SPayLaterPaymentService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function payInstallment(
        WorkDay $workDay,
        DebtInstallment $installment,
        Account $paymentAccount,
        int $actualPaidAmount,
        int $promoDiscountAmount = 0
    ): Transaction {
        if ($installment->isPaid()) {
            throw new InvalidArgumentException("Cicilan ini sudah pernah dibayar lunas.");
        }

        $totalSettlement = $actualPaidAmount + $promoDiscountAmount;
        if ($totalSettlement !== $installment->amount_due) {
            throw new InvalidArgumentException(
                "Total pembayaran (Rp{$actualPaidAmount}) + diskon promo (Rp{$promoDiscountAmount}) harus sama dengan tagihan cicilan (Rp{$installment->amount_due})."
            );
        }

        return DB::transaction(function () use ($workDay, $installment, $paymentAccount, $actualPaidAmount, $promoDiscountAmount) {
            $liabilityAccountName = 'Kewajiban Utang - ' . $installment->debt->name;
            $spaylaterLiabilityAccount = Account::firstOrCreate(
                ['name' => $liabilityAccountName],
                [
                    'type' => AccountType::DEBT,
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );

            $discountIncomeAccount = Account::firstOrCreate(
                ['name' => 'Pendapatan Diskon / Promo Finansial'],
                [
                    'type' => AccountType::INCOME,
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );

            $entries = [];

            // 1. Debit akun kewajiban sebesar tagihan utuh (Kewajiban berkurang)
            $entries[] = [
                'account_id' => $spaylaterLiabilityAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $installment->amount_due,
            ];

            // 2. Kredit akun bank/kas pembayar sebesar kas riil keluar (Aset berkurang)
            $entries[] = [
                'account_id' => $paymentAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $actualPaidAmount,
            ];

            // 3. Kredit akun pendapatan diskon jika ada promo > 0 (Pendapatan bertambah)
            if ($promoDiscountAmount > 0) {
                $entries[] = [
                    'account_id' => $discountIncomeAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $promoDiscountAmount,
                ];
            }

            $transaction = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'debt_repayment',
                description: "Bayar SPayLater: {$installment->debt->name} (Cicilan #{$installment->installment_number})",
                transactionTime: now(),
                entries: $entries,
                isEstimatedTime: false,
                metadata: [
                    'debt_id' => $installment->debt_id,
                    'installment_id' => $installment->id,
                    'actual_paid' => $actualPaidAmount,
                    'promo_discount' => $promoDiscountAmount,
                ]
            );

            $installment->update([
                'amount_paid' => $actualPaidAmount,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Cek apakah seluruh cicilan pada utang ini sudah lunas
            $hasUnpaid = DebtInstallment::where('debt_id', $installment->debt_id)
                ->where('status', 'unpaid')
                ->exists();

            if (!$hasUnpaid) {
                $installment->debt->update(['status' => 'paid_off']);
            }

            return $transaction;
        });
    }
}
