<?php

namespace App\Http\Controllers\Api;

use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalExpenseRequest;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;

class PersonalExpenseController extends Controller
{
    public function store(
        PersonalExpenseRequest $request,
        LedgerService $ledgerService
    ): JsonResponse {
        $validated = $request->validated();
        
        $workDay = isset($validated['work_day_id'])
            ? WorkDay::findOrFail($validated['work_day_id'])
            : WorkDay::latest('date')->firstOrFail();

        $accountId = $validated['account_id'] ?? $validated['source_account_id'] ?? null;

        // Default ke akun BCA jika account_id tidak disertakan
        $sourceAccount = $accountId
            ? Account::findOrFail($accountId)
            : Account::firstOrCreate(
                ['name' => 'BCA'],
                ['type' => 'asset', 'balance' => 0]
            );

        $expenseAccount = Account::firstOrCreate(
            ['name' => 'Beban Makanan & Minuman Driver'],
            ['type' => 'expense', 'balance' => 0]
        );

        $description = $validated['description'] ?? $validated['notes'] ?? 'Pengeluaran Pribadi Driver';
        $amount = (int) $validated['amount'];

        $transaction = $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $description,
            transactionTime: now(),
            entries: [
                ['account_id' => $sourceAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $amount],
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $amount],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'personal_on_beat_expense']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pengeluaran pribadi berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'account_name' => $sourceAccount->name,
                'description' => $description,
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
