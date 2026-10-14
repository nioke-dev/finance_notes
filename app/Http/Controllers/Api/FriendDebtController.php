<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FriendDebtPaymentRequest;
use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use App\Services\FriendDebtSettlementService;
use Illuminate\Http\JsonResponse;

class FriendDebtController extends Controller
{
    public function pay(
        FriendDebtPaymentRequest $request,
        FriendDebtSettlementService $settlementService
    ): JsonResponse {
        $validated = $request->validated();

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $debt = Debt::findOrFail($validated['debt_id']);
        $account = Account::findOrFail($validated['account_id']);

        $remainder = max(0, $debt->total_obligation - $validated['received_amount']);

        $transaction = $settlementService->settleFriendDebt(
            workDay: $workDay,
            debt: $debt,
            receivingAccount: $account,
            receivedAmount: $validated['received_amount'],
            forgiveRemainder: (bool) $validated['forgive_remainder']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pembayaran utang teman berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'borrower_name' => $debt->borrower_name,
                'received_amount' => $validated['received_amount'],
                'remainder' => $remainder,
                'is_paid_off' => $debt->fresh()->isPaidOff(),
                'charity_recorded' => $validated['forgive_remainder'] ? $remainder : 0,
            ],
        ], 200);
    }
}
