<?php

namespace Tests\Feature\Web;

use App\Models\DriverSnapshot;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $day1 = WorkDay::create([
            'work_date' => now()->subDays(2)->toDateString(),
            'status' => WorkDay::STATUS_FINAL,
            'total_orders' => 18,
            'online_hours' => 6.5,
        ]);

        DriverSnapshot::create([
            'work_day_id' => $day1->id,
            'recorded_at' => now()->subDays(2),
            'completed_orders' => 18,
            'hub_income' => 144000,
            'hub_bonus' => 45000,
            'customer_tips' => 6000,
            'shopeepay_balance' => 195000,
            'gopay_balance' => 0,
            'gopay_merchant_balance' => 0,
        ]);

        $day2 = WorkDay::create([
            'work_date' => now()->subDay()->toDateString(),
            'status' => WorkDay::STATUS_FINAL,
            'total_orders' => 10,
            'online_hours' => 4.0,
        ]);

        DriverSnapshot::create([
            'work_day_id' => $day2->id,
            'recorded_at' => now()->subDay(),
            'completed_orders' => 10,
            'hub_income' => 80000,
            'hub_bonus' => 0,
            'customer_tips' => 4000,
            'shopeepay_balance' => 84000,
            'gopay_balance' => 0,
            'gopay_merchant_balance' => 0,
        ]);
    }

    public function test_driver_analytics_page_can_be_accessed_and_displays_kpi_cards(): void
    {
        $response = $this->get('/analytics/driver');

        $response->assertStatus(200);
        $response->assertSee('Performa Driver ShopeeFood Malang');
        $response->assertSee('Total Order Selesai');
        $response->assertSee('28'); // 18 + 10 trips
        $response->assertSee('Total Pendapatan Shopee');
        // Total Shopee Income = (144.000 + 45.000 + 6.000) + (80.000 + 0 + 4.000) = 195.000 + 84.000 = 279.000
        $response->assertSee('Rp 279.000');
        $response->assertSee('Frekuensi Tembus Bonus');
        $response->assertSee('1'); // 1 hari bonus > 0
    }

    public function test_driver_analytics_supports_period_filtering(): void
    {
        $response = $this->get('/analytics/driver?days=30');

        $response->assertStatus(200);
        $response->assertSee('30 Hari Terakhir');
        $response->assertSee('28');
    }
}
