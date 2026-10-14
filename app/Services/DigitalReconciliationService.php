<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\WorkDay;
use Illuminate\Support\Facades\DB;

class DigitalReconciliationService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Rekonsiliasi Saldo Digital Malam Hari (Snapshot 4 Saldo Digital & Deteksi Surplus QRIS)
     *
     * @param WorkDay $workDay
     * @param array<int, array{account_id: int, actual_balance: int}> $accountBalances
     * @return array{adjusted_count: int, total_surplus: int, total_shortage: int}
     */
    public function reconcileDigitalAccounts(WorkDay $workDay, array $accountBalances): array
    {
        return DB::transaction(function () use ($workDay, $accountBalances) {
            $adjustedCount = 0;
            $totalSurplus = 0;
            $totalShortage = 0;

            foreach ($accountBalances as $item) {
                $accountId = (int) $item['account_id'];
                $actualBalance = (int) $item['actual_balance'];

                $account = Account::findOrFail($accountId);
                $diff = $actualBalance - $account->current_balance;

                if ($diff === 0) {
                    continue;
                }

                $adjustedCount++;

                if ($diff > 0) {
                    // Surplus terdeteksi (Tip QRIS nyusul di GoPay/Merchant atau penambahan saldo ShopeeFood Driver)
                    $totalSurplus += $diff;

                    $surplusAccount = in_array($account->name, ['GoPay', 'GoPay Merchant', 'ShopeePay'])
                        ? Account::firstOrCreate(
                            ['name' => 'Pendapatan QRIS / Tip Non-Tunai'],
                            ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                        )
                        : Account::firstOrCreate(
                            ['name' => 'Pendapatan Selisih Kas (Surplus Tak Terlacak)'],
                            ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                        );

                    $tx = $this->ledgerService->recordTransaction(
                        workDay: $workDay,
                        type: 'income',
                        description: "Surplus Digital Nyusul {$account->name}: +Rp " . number_format($diff, 0, ',', '.'),
                        transactionTime: now(),
                        entries: [
                            ['account_id' => $account->id, 'entry_type' => EntryType::DEBIT, 'amount' => $diff],
                            ['account_id' => $surplusAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $diff],
                        ],
                        isEstimatedTime: false,
                        metadata: [
                            'type' => 'digital_reconciliation_surplus',
                            'account_id' => $account->id,
                            'diff' => $diff,
                        ]
                    );

                    AuditLog::create([
                        'auditable_type' => Account::class,
                        'auditable_id' => $account->id,
                        'action' => 'digital_reconciliation_surplus',
                        'old_values' => ['balance' => $account->current_balance],
                        'new_values' => ['balance' => $actualBalance, 'surplus' => $diff],
                        'reason' => "Penyelarasan saldo digital {$account->name}: surplus +Rp " . number_format($diff, 0, ',', '.') . " diakui sebagai pendapatan digital nyusul.",
                    ]);
                } else {
                    // Shortage terdeteksi (Biaya admin bank, potongan auto-debit, atau selisih kurang)
                    $shortageAmount = abs($diff);
                    $totalShortage += $shortageAmount;

                    $expenseAccount = Account::firstOrCreate(
                        ['name' => 'Beban Biaya Admin Transfer'],
                        ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
                    );

                    $tx = $this->ledgerService->recordTransaction(
                        workDay: $workDay,
                        type: 'expense',
                        description: "Penyesuaian Saldo Digital {$account->name}: -Rp " . number_format($shortageAmount, 0, ',', '.'),
                        transactionTime: now(),
                        entries: [
                            ['account_id' => $account->id, 'entry_type' => EntryType::CREDIT, 'amount' => $shortageAmount],
                            ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $shortageAmount],
                        ],
                        isEstimatedTime: false,
                        metadata: [
                            'type' => 'digital_reconciliation_shortage',
                            'account_id' => $account->id,
                            'diff' => $diff,
                        ]
                    );

                    AuditLog::create([
                        'auditable_type' => Account::class,
                        'auditable_id' => $account->id,
                        'action' => 'digital_reconciliation_shortage',
                        'old_values' => ['balance' => $account->current_balance],
                        'new_values' => ['balance' => $actualBalance, 'shortage' => $shortageAmount],
                        'reason' => "Penyelarasan saldo digital {$account->name}: selisih -Rp " . number_format($shortageAmount, 0, ',', '.') . " disesuaikan.",
                    ]);
                }
            }

            return [
                'adjusted_count' => $adjustedCount,
                'total_surplus' => $totalSurplus,
                'total_shortage' => $totalShortage,
            ];
        });
    }
}
