<?php

namespace Tests\Unit\Models;

use App\Models\DriverSnapshot;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DriverSnapshotModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_snapshot_belongs_to_work_day(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $snapshot = DriverSnapshot::create([
            'work_day_id' => $workDay->id,
            'recorded_at' => now(),
            'completed_orders' => 10,
            'hub_income' => 80000,
            'hub_bonus' => 0,
            'customer_tips' => 5000,
            'shopeepay_balance' => 85000,
            'gopay_balance' => 20000,
            'gopay_merchant_balance' => 0,
        ]);

        $this->assertInstanceOf(WorkDay::class, $snapshot->workDay);
        $this->assertSame($workDay->id, $snapshot->workDay->id);
        $this->assertInstanceOf(Carbon::class, $snapshot->recorded_at);
    }

    public function test_total_driver_income_accessor_calculates_sum_accurately(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $snapshot = DriverSnapshot::create([
            'work_day_id' => $workDay->id,
            'recorded_at' => now(),
            'completed_orders' => 26,
            'hub_income' => 208000,
            'hub_bonus' => 14500,
            'customer_tips' => 12000,
            'shopeepay_balance' => 234500,
            'gopay_balance' => 50000,
            'gopay_merchant_balance' => 15000,
        ]);

        // 208.000 + 14.500 + 12.000 = 234.500
        $this->assertSame(234500, $snapshot->total_driver_income);
    }

    public function test_total_digital_holding_and_settlement_status_accessors(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        // Case 1: Merchant balance >= 10.000 -> ready_to_settle
        $snapshot1 = DriverSnapshot::create([
            'work_day_id' => $workDay->id,
            'recorded_at' => now(),
            'shopeepay_balance' => 100000,
            'gopay_balance' => 50000,
            'gopay_merchant_balance' => 12000,
        ]);

        // 100.000 + 50.000 + 12.000 = 162.000
        $this->assertSame(162000, $snapshot1->total_digital_holding);
        $this->assertSame('ready_to_settle', $snapshot1->merchant_settlement_status);

        // Case 2: Merchant balance < 10.000 -> holding
        $snapshot2 = DriverSnapshot::create([
            'work_day_id' => $workDay->id,
            'recorded_at' => now(),
            'shopeepay_balance' => 50000,
            'gopay_balance' => 10000,
            'gopay_merchant_balance' => 8000,
        ]);

        $this->assertSame(68000, $snapshot2->total_digital_holding);
        $this->assertSame('holding', $snapshot2->merchant_settlement_status);
    }
}
