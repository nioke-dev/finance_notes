<?php

namespace Tests\Unit\Migrations;

use App\Models\WorkDay;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashDenominationsTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_denominations_table_has_all_currency_breakdown_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('cash_denominations', [
                'id',
                'work_day_id',
                'coin_100',
                'coin_200',
                'coin_500',
                'coin_1000',
                'bill_1000',
                'bill_2000',
                'bill_5000',
                'bill_10000',
                'bill_20000',
                'bill_50000',
                'bill_100000',
                'total_physical_amount',
                'created_at',
                'updated_at',
            ])
        );
    }

    public function test_work_day_id_is_strictly_unique(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        DB::table('cash_denominations')->insert([
            'work_day_id' => $workDay->id,
            'bill_100000' => 5,
            'total_physical_amount' => 500000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('cash_denominations')->insert([
            'work_day_id' => $workDay->id,
            'bill_100000' => 2,
            'total_physical_amount' => 200000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
