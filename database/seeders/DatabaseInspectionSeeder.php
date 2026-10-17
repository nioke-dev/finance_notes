<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CashDenomination;
use App\Models\WorkDay;
use Illuminate\Database\Seeder;

class DatabaseInspectionSeeder extends Seeder
{
    public function run(): void
    {
        $cashAccounts = Account::where('name', 'Cash')
            ->orWhere('type', 'cash')
            ->get(['id', 'name', 'type', 'current_balance', 'initial_balance', 'metadata'])
            ->toArray();

        $denoms = CashDenomination::all()->toArray();

        $auditLogs = AuditLog::where('auditable_type', 'LIKE', '%Account%')
            ->orWhere('auditable_type', 'LIKE', '%Cash%')
            ->latest('id')
            ->take(10)
            ->get(['id', 'auditable_type', 'auditable_id', 'action', 'old_values', 'new_values', 'reason', 'created_at'])
            ->toArray();

        $workDays = WorkDay::all(['id', 'work_date', 'day_type', 'status', 'created_at'])->toArray();

        $this->command->info("=== CASH ACCOUNTS ===");
        $this->command->info(json_encode($cashAccounts, JSON_PRETTY_PRINT));

        $this->command->info("=== CASH DENOMINATIONS ===");
        $this->command->info(json_encode($denoms, JSON_PRETTY_PRINT));

        $this->command->info("=== AUDIT LOGS ===");
        $this->command->info(json_encode($auditLogs, JSON_PRETTY_PRINT));

        $this->command->info("=== WORK DAYS ===");
        $this->command->info(json_encode($workDays, JSON_PRETTY_PRINT));
    }
}
