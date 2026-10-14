<?php

namespace Tests\Unit\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverSnapshotsTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_snapshots_table_has_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('driver_snapshots'));
        $this->assertTrue(Schema::hasColumns('driver_snapshots', [
            'id',
            'work_day_id',
            'recorded_at',
            'completed_orders',
            'hub_income',
            'hub_bonus',
            'customer_tips',
            'shopeepay_balance',
            'gopay_balance',
            'gopay_merchant_balance',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_multiple_snapshots_can_be_created_for_same_work_day(): void
    {
        $workDayId = DB::table('work_days')->insertGetId([
            'work_date' => '2026-10-14',
            'status' => 'needs_reconciliation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Snapshot jam 15.00
        DB::table('driver_snapshots')->insert([
            'work_day_id' => $workDayId,
            'recorded_at' => '2026-10-14 15:00:00',
            'completed_orders' => 12,
            'hub_income' => 96000,
            'hub_bonus' => 0,
            'customer_tips' => 4000,
            'shopeepay_balance' => 100000,
            'gopay_balance' => 25000,
            'gopay_merchant_balance' => 15000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Snapshot jam 20.00 (data bertambah, bukan overwrite)
        DB::table('driver_snapshots')->insert([
            'work_day_id' => $workDayId,
            'recorded_at' => '2026-10-14 20:00:00',
            'completed_orders' => 22,
            'hub_income' => 176000,
            'hub_bonus' => 8000,
            'customer_tips' => 10000,
            'shopeepay_balance' => 194000,
            'gopay_balance' => 40000,
            'gopay_merchant_balance' => 30000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshots = DB::table('driver_snapshots')->where('work_day_id', $workDayId)->get();
        $this->assertCount(2, $snapshots);
        $this->assertSame(12, (int) $snapshots->first()->completed_orders);
        $this->assertSame(22, (int) $snapshots->last()->completed_orders);
    }
}
