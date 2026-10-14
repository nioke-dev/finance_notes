<?php

namespace Tests\Unit\Services;

use App\Services\ShopeeBonusEligibilityService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ShopeeBonusEligibilityServiceTest extends TestCase
{
    protected ShopeeBonusEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShopeeBonusEligibilityService();
    }

    public function test_orders_before_10_am_are_ineligible(): void
    {
        $this->assertFalse($this->service->isTimeEligible(Carbon::parse('2026-10-14 07:30:00')));
        $this->assertFalse($this->service->isTimeEligible(Carbon::parse('2026-10-14 09:59:59')));
    }

    public function test_orders_between_10_am_and_8_pm_are_eligible(): void
    {
        $this->assertTrue($this->service->isTimeEligible(Carbon::parse('2026-10-14 10:00:00')));
        $this->assertTrue($this->service->isTimeEligible(Carbon::parse('2026-10-14 12:45:00')));
        $this->assertTrue($this->service->isTimeEligible(Carbon::parse('2026-10-14 19:59:59')));
        $this->assertTrue($this->service->isTimeEligible(Carbon::parse('2026-10-14 20:00:00')));
    }

    public function test_orders_after_8_pm_are_ineligible(): void
    {
        $this->assertFalse($this->service->isTimeEligible(Carbon::parse('2026-10-14 20:00:01')));
        $this->assertFalse($this->service->isTimeEligible(Carbon::parse('2026-10-14 21:30:00')));
    }

    public function test_filter_eligible_orders_extracts_correct_count_from_mixed_dataset(): void
    {
        $orders = [];

        // 5 pesanan sebelum jam 10 (08.00 - 09.30)
        for ($i = 1; $i <= 5; $i++) {
            $orders[] = [
                'order_number' => "SF-EARLY-{$i}",
                'completed_at' => Carbon::parse("2026-10-14 08:{$i}:00"),
            ];
        }

        // 20 pesanan dalam rentang eligible 10.00 - 20.00
        for ($i = 1; $i <= 20; $i++) {
            $orders[] = [
                'order_number' => "SF-VALID-{$i}",
                'completed_at' => Carbon::parse("2026-10-14 11:{$i}:00"),
            ];
        }

        // 5 pesanan setelah jam 20 (20.30 - 21.30)
        for ($i = 1; $i <= 5; $i++) {
            $orders[] = [
                'order_number' => "SF-LATE-{$i}",
                'completed_at' => Carbon::parse("2026-10-14 20:{$i}:30"),
            ];
        }

        $filtered = $this->service->filterEligibleOrders($orders);

        $this->assertCount(20, $filtered);
        $this->assertSame('SF-VALID-1', $filtered->first()['order_number']);
        $this->assertSame('SF-VALID-20', $filtered->last()['order_number']);
    }
}
