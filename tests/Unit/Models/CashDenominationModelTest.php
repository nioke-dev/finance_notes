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

    public function test_total_pieces_and_smart_recommendations(): void
    {
        $denomination = new CashDenomination([
            'bill_5000' => 25,  // Threshold 20 -> excess
            'bill_2000' => 2,   // Total change 27 -> no low change warning
            'coin_1000' => 5,
        ]);

        $this->assertEquals(32, $denomination->total_pieces);

        $recs = $denomination->getRecommendations();
        $this->assertNotEmpty($recs);

        $excess = collect($recs)->firstWhere('type', 'excess');
        $this->assertNotNull($excess);
        $this->assertEquals('bill_5000', $excess['field']);
        $this->assertEquals(25, $excess['count']);
        $this->assertStringContainsString('Beban dompet menumpuk', $excess['message']);
    }

    public function test_low_change_warning_when_small_bills_are_insufficient(): void
    {
        $denomination = new CashDenomination([
            'bill_100000' => 5,
            'bill_50000' => 2,
            'bill_5000' => 1,
            'bill_2000' => 1, // Total 2000 + 5000 = 2 (< 5 threshold)
        ]);

        $recs = $denomination->getRecommendations();
        $warning = collect($recs)->firstWhere('type', 'warning');
        $this->assertNotNull($warning);
        $this->assertEquals('low_change', $warning['field']);
        $this->assertStringContainsString('Pecahan Kembalian COD', $warning['label']);
    }
}
