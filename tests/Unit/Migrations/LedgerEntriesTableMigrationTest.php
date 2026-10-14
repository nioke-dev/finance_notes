<?php

namespace Tests\Unit\Migrations;

use App\Enums\AccountType;
use App\Enums\EntryType;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LedgerEntriesTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_entries_table_has_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('ledger_entries'));
        $this->assertTrue(Schema::hasColumns('ledger_entries', [
            'id',
            'transaction_id',
            'account_id',
            'entry_type',
            'amount',
            'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('ledger_entries', 'updated_at'));
    }

    public function test_cannot_delete_account_that_has_ledger_entries(): void
    {
        $workDayId = DB::table('work_days')->insertGetId([
            'work_date' => '2026-10-14',
            'status' => 'needs_reconciliation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Kas Tunai Dompet',
            'type' => AccountType::CASH->value,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $txId = DB::table('transactions')->insertGetId([
            'work_day_id' => $workDayId,
            'type' => 'expense',
            'description' => 'Beli bensin',
            'transaction_time' => now(),
            'is_estimated_time' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ledger_entries')->insert([
            'transaction_id' => $txId,
            'account_id' => $accountId,
            'entry_type' => EntryType::CREDIT->value,
            'amount' => 15000,
            'created_at' => now(),
        ]);

        // Attempting to delete account with FK restrict must fail
        $this->expectException(QueryException::class);
        DB::table('accounts')->where('id', $accountId)->delete();
    }

    public function test_cannot_delete_transaction_that_has_ledger_entries(): void
    {
        $workDayId = DB::table('work_days')->insertGetId([
            'work_date' => '2026-10-14',
            'status' => 'needs_reconciliation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Beban Bensin',
            'type' => AccountType::EXPENSE->value,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $txId = DB::table('transactions')->insertGetId([
            'work_day_id' => $workDayId,
            'type' => 'expense',
            'description' => 'Beli bensin',
            'transaction_time' => now(),
            'is_estimated_time' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ledger_entries')->insert([
            'transaction_id' => $txId,
            'account_id' => $accountId,
            'entry_type' => EntryType::DEBIT->value,
            'amount' => 15000,
            'created_at' => now(),
        ]);

        // Attempting to delete transaction with FK restrict must fail
        $this->expectException(QueryException::class);
        DB::table('transactions')->where('id', $txId)->delete();
    }

    public function test_ledger_entries_amount_is_bigint_without_decimals(): void
    {
        $workDayId = DB::table('work_days')->insertGetId([
            'work_date' => '2026-10-14',
            'status' => 'needs_reconciliation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Pendapatan ShopeeFood',
            'type' => AccountType::INCOME->value,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $txId = DB::table('transactions')->insertGetId([
            'work_day_id' => $workDayId,
            'type' => 'income',
            'description' => 'Pendapatan trip',
            'transaction_time' => now(),
            'is_estimated_time' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entryId = DB::table('ledger_entries')->insertGetId([
            'transaction_id' => $txId,
            'account_id' => $accountId,
            'entry_type' => EntryType::CREDIT->value,
            'amount' => 12500,
            'created_at' => now(),
        ]);

        $entry = DB::table('ledger_entries')->where('id', $entryId)->first();
        $this->assertSame(12500, (int) $entry->amount);
    }
}
