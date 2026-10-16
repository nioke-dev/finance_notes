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

        $cashback = (int) ($validated['cashback_amount'] ?? 0);
        $cashbackTx = null;
        if ($cashback > 0 && str_contains(strtolower($sourceAccount->name), 'shopeepay')) {
            $cashbackIncomeAccount = Account::firstOrCreate(
                ['name' => 'Pendapatan Cashback & Reward ShopeePay'],
                ['type' => \App\Enums\AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
            );
            $cashbackTx = $ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'income',
                description: "[Cashback ShopeePay] Reward atas {$description}",
                transactionTime: now(),
                entries: [
                    ['account_id' => $sourceAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $cashback],
                    ['account_id' => $cashbackIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $cashback],
                ],
                isEstimatedTime: false,
                metadata: [
                    'type' => 'shopeepay_cashback',
                    'source_account_id' => $sourceAccount->id,
                    'cashback_amount' => $cashback,
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengeluaran pribadi berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'account_name' => $sourceAccount->name,
                'description' => $description,
                'cashback_amount' => $cashback,
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
