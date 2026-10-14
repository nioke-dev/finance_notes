<?php

namespace Tests\Unit\Migrations;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionsTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_table_has_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('transactions'));
        $this->assertTrue(Schema::hasColumns('transactions', [
            'id',
            'work_day_id',
            'type',
            'description',
            'transaction_time',
            'is_estimated_time',
            'metadata',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_cannot_delete_work_day_with_associated_transactions(): void
    {
        $workDayId = DB::table('work_days')->insertGetId([
            'work_date' => '2026-10-14',
            'status' => 'needs_reconciliation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'work_day_id' => $workDayId,
            'type' => 'expense',
            'description' => 'Bensin Pertalite SPBU',
            'transaction_time' => now(),
            'is_estimated_time' => false,
            'metadata' => json_encode(['vendor' => 'spbu', 'liters' => 3.5]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Trying to delete work_day with restricted FK must throw QueryException
        $this->expectException(QueryException::class);
        DB::table('work_days')->where('id', $workDayId)->delete();
    }

    public function test_metadata_column_can_store_and_retrieve_json(): void
    {
        $workDayId = DB::table('work_days')->insertGetId([
            'work_date' => '2026-10-15',
            'status' => 'needs_reconciliation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'gps' => ['lat' => -7.965, 'lng' => 112.632],
            'spot' => 'Parkir Bakso Damas',
            'order_id' => 'SF-MLG-10293',
        ];

        $txId = DB::table('transactions')->insertGetId([
            'work_day_id' => $workDayId,
            'type' => 'expense',
            'description' => 'Parkir Bakso Damas',
            'transaction_time' => now(),
            'is_estimated_time' => false,
            'metadata' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $retrieved = DB::table('transactions')->where('id', $txId)->first();
        $this->assertNotNull($retrieved);
        $decoded = json_decode($retrieved->metadata, true);
        $this->assertSame('Parkir Bakso Damas', $decoded['spot']);
        $this->assertSame(-7.965, $decoded['gps']['lat']);
    }
}
