<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;

class ReconciliationEngineService
{
    /**
     * @param array $digitalActualBalances Array of ['account_id' => int, 'actual_balance' => int]
     */
    public function reconcileWorkDay(WorkDay $workDay, array $digitalActualBalances): array
    {
        $report = [
            'work_day_id' => $workDay->id,
            'is_balanced' => true,
            'total_absolute_discrepancy' => 0,
            'accounts' => [],
        ];

        // 1. Rekonsiliasi Akun Cash (Uang Fisik)
        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $physicalRecord = CashDenomination::where('work_day_id', $workDay->id)->first();
        $physicalCashActual = $physicalRecord ? (int) $physicalRecord->total_physical_amount : 0;

        $cashDiscrepancy = $physicalCashActual - (int) $cashAccount->current_balance;

        $report['accounts'][] = [
            'account_id' => $cashAccount->id,
            'account_name' => 'Cash',
            'type' => 'physical',
            'ledger_balance' => (int) $cashAccount->current_balance,
            'actual_balance' => $physicalCashActual,
            'discrepancy' => $cashDiscrepancy,
            'status' => $this->resolveStatus($cashDiscrepancy),
        ];

        if ($cashDiscrepancy !== 0) {
            $report['is_balanced'] = false;
            $report['total_absolute_discrepancy'] += abs($cashDiscrepancy);
        }

        // 2. Rekonsiliasi Akun Digital
        $actualMap = collect($digitalActualBalances)->pluck('actual_balance', 'account_id');
        $digitalAccounts = Account::digital()->active()->get();

        foreach ($digitalAccounts as $account) {
            $actual = $actualMap->has($account->id)
                ? (int) $actualMap->get($account->id)
                : (int) $account->current_balance;

            $discrepancy = $actual - (int) $account->current_balance;

            $report['accounts'][] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'type' => 'digital',
                'ledger_balance' => (int) $account->current_balance,
                'actual_balance' => $actual,
                'discrepancy' => $discrepancy,
                'status' => $this->resolveStatus($discrepancy),
            ];

            if ($discrepancy !== 0) {
                $report['is_balanced'] = false;
                $report['total_absolute_discrepancy'] += abs($discrepancy);
            }
        }

        return $report;
    }

    protected function resolveStatus(int $discrepancy): string
    {
        if ($discrepancy === 0) {
            return 'matched';
        }

        return $discrepancy > 0 ? 'surplus' : 'shortage';
    }
}
