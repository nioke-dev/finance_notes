<?php

namespace App\Http\Controllers\Api;

use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmBonusDisbursementRequest;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;

class BonusDisbursementController extends Controller
{
    public function confirm(
        ConfirmBonusDisbursementRequest $request,
        LedgerService $ledgerService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        if (!$validated['is_disbursed'] || $validated['bonus_amount'] === 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'Status pencairan bonus ditandai belum masuk. Saldo ShopeePay belum disesuaikan.',
                'data' => ['is_disbursed' => false, 'credited_amount' => 0],
            ], 200);
        }

        $shopeepayAccount = Account::where('name', 'ShopeePay')->firstOrFail();
        $bonusIncomeAccount = Account::where('name', 'Bonus Hub ShopeeFood')->firstOrFail();

        $transaction = $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'income',
            description: "Pencairan Bonus Hub ShopeeFood Malam (Rp{$validated['bonus_amount']})",
            transactionTime: now(),
            entries: [
                ['account_id' => $shopeepayAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $validated['bonus_amount']],
                ['account_id' => $bonusIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $validated['bonus_amount']],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'shopeepay_bonus_disbursement', 'amount' => $validated['bonus_amount']]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Bonus Hub berhasil dicairkan ke saldo ShopeePay.',
            'data' => [
                'transaction_id' => $transaction->id,
                'is_disbursed' => true,
                'credited_amount' => $validated['bonus_amount'],
                'shopeepay_current_balance' => $shopeepayAccount->fresh()->current_balance,
            ],
        ], 200);
    }
}
