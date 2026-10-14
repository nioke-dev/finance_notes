<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuickFuelRequest;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\QuickFuelService;
use Illuminate\Http\JsonResponse;

class QuickFuelController extends Controller
{
    public function store(
        QuickFuelRequest $request,
        QuickFuelService $fuelService
    ): JsonResponse {
        $validated = $request->validated();

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $accountId = $validated['account_id'] ?? $validated['source_account_id'];
        $vendorType = $validated['vendor_type'] ?? $validated['type'];

        $account = Account::findOrFail($accountId);

        $transaction = $fuelService->recordFuel(
            workDay: $workDay,
            sourceAccount: $account,
            vendorType: $vendorType,
            amount: (int) $validated['amount']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pengeluaran bensin berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => (int) $validated['amount'],
                'vendor_type' => $vendorType,
                'estimated_volume' => $transaction->metadata['estimated_volume'],
                'account_name' => $account->name,
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
