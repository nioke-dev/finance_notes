<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountsTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi kolom yang diharapkan pada tabel accounts.
     */
    public function test_accounts_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('accounts'));

        $expectedColumns = [
            'id',
            'name',
            'type',
            'current_balance',
            'initial_balance',
            'is_active',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('accounts', $column),
                "Kolom {$column} tidak ditemukan di tabel accounts."
            );
        }
    }

    /**
     * Memverifikasi default value current_balance dan initial_balance adalah 0.
     */
    public function test_current_balance_column_defaults_to_zero(): void
    {
        $id = DB::table('accounts')->insertGetId([
            'name' => 'BCA',
            'type' => 'bank',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $account = DB::table('accounts')->where('id', $id)->first();

        $this->assertEquals(0, $account->current_balance);
        $this->assertEquals(0, $account->initial_balance);
        $this->assertEquals(1, $account->is_active);
    }

    /**
     * Memverifikasi tabel accounts dapat di-rollback.
     */
    public function test_accounts_table_can_be_rolled_back(): void
    {
        $this->assertTrue(Schema::hasTable('accounts'));

        $this->artisan('migrate:rollback');

        $this->assertFalse(Schema::hasTable('accounts'));
    }
}
