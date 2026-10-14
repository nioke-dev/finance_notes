<?php

namespace Tests\Unit\Models;

use App\Models\CashDenomination;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashDenominationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_sum_returns_exact_currency_total(): void
    {
        $data = [
            'coin_500' => 4,    // 2.000
            'bill_2000' => 5,   // 10.000
            'bill_50000' => 2,  // 100.000
        ];

        $total = CashDenomination::calculateSum($data);

        $this->assertEquals(112000, $total);
    }

    public function test_saving_event_automatically_updates_total_physical_amount(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $denomination = CashDenomination::create([
            'work_day_id' => $workDay->id,
            'coin_500' => 4,
            'bill_2000' => 5,
            'bill_50000' => 2,
        ]);

        $this->assertEquals(112000, $denomination->fresh()->total_physical_amount);
        $this->assertInstanceOf(WorkDay::class, $denomination->workDay);
        $this->assertEquals($workDay->id, $denomination->workDay->id);
    }
}
