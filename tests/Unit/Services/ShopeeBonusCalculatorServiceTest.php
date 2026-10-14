<?php

namespace Tests\Unit\Services;

use App\Models\ShopeeBonusScheme;
use App\Services\ShopeeBonusCalculatorService;
use Database\Seeders\ShopeeBonusSchemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopeeBonusCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShopeeBonusCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShopeeBonusSchemeSeeder::class);
        $this->calculator = new ShopeeBonusCalculatorService();
    }

    public function test_orders_below_or_equal_to_15_returns_zero_bonus(): void
    {
        $this->assertSame(0, $this->calculator->calculateBonus(0));
        $this->assertSame(0, $this->calculator->calculateBonus(10));
        $this->assertSame(0, $this->calculator->calculateBonus(15));
    }

    public function test_orders_in_tier_two_calculates_correctly(): void
    {
        // 16 orders = (16 - 15) * 1000 = 1.000
        $this->assertSame(1000, $this->calculator->calculateBonus(16));

        // 18 orders = (18 - 15) * 1000 = 3.000
        $this->assertSame(3000, $this->calculator->calculateBonus(18));

        // 20 orders = (20 - 15) * 1000 = 5.000
        $this->assertSame(5000, $this->calculator->calculateBonus(20));
    }

    public function test_sample_case_26_orders_returns_14500_rupiah_exact(): void
    {
        // 1-15: 0
        // 16-20: 5 * 1000 = 5.000
        // 21-25: 5 * 1500 = 7.500
        // 26: 1 * 2000 = 2.000
        // Total = 5.000 + 7.500 + 2.000 = 14.500
        $this->assertSame(14500, $this->calculator->calculateBonus(26));
    }

    public function test_sample_case_31_orders_returns_24500_rupiah_exact(): void
    {
        // 1-15: 0
        // 16-20: 5.000
        // 21-25: 7.500
        // 26-31: 6 * 2000 = 12.000
        // Total = 5.000 + 7.500 + 12.000 = 24.500
        $this->assertSame(24500, $this->calculator->calculateBonus(31));
    }

    public function test_fallback_calculation_when_database_is_empty(): void
    {
        ShopeeBonusScheme::query()->delete();

        $this->assertSame(0, $this->calculator->calculateBonus(15));
        $this->assertSame(3000, $this->calculator->calculateBonus(18));
        $this->assertSame(14500, $this->calculator->calculateBonus(26));
        $this->assertSame(24500, $this->calculator->calculateBonus(31));
    }
}
