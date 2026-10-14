<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class TransferController extends Controller
{
    public function store(TransferRequest $request, TransferService $transferService): JsonResponse
    {
        $validated = $request->validated();

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $toAccount = Account::findOrFail($validated['to_account_id']);

        $transaction = $transferService->transfer(
            workDay: $workDay,
            sourceAccount: $fromAccount,
            destinationAccount: $toAccount,
            amount: $validated['amount'],
            adminFee: $validated['admin_fee'] ?? 0,
            transactionTime: isset($validated['transaction_time']) ? Carbon::parse($validated['transaction_time']) : now(),
            note: $validated['note'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Transfer berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'from_account' => $fromAccount->name,
                'to_account' => $toAccount->name,
                'amount' => $validated['amount'],
                'admin_fee' => $validated['admin_fee'] ?? 0,
            ],
        ], 201);
    }
}
