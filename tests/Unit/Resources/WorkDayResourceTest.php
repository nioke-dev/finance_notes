<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\WorkDayResource;
use App\Models\DriverSnapshot;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WorkDayResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_day_resource_formats_date_and_totals_correctly(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_FINAL,
            'total_orders' => 18,
            'closing_notes' => 'Hari kerja selesai target bonus tercapai',
        ]);

        DriverSnapshot::create([
            'work_day_id' => $workDay->id,
            'recorded_at' => now(),
            'completed_orders' => 18,
            'hub_income' => 144000,
            'hub_bonus' => 45000,
            'customer_tips' => 6000,
            'shopeepay_balance' => 195000,
            'gopay_balance' => 20000,
            'gopay_merchant_balance' => 15000,
        ]);

        $resource = new WorkDayResource($workDay);
        $array = $resource->toArray(Request::create('/api/v1/work-days/' . $workDay->id));

        $this->assertEquals($workDay->id, $array['id']);
        $this->assertEquals('2026-10-14', $array['date']);
        $this->assertNotNull($array['formatted_date']);
        $this->assertEquals(WorkDay::STATUS_FINAL, $array['status']);
        $this->assertEquals(18, $array['total_trips']);
        $this->assertEquals(195000, $array['total_shopee_income']);
        $this->assertTrue($array['is_balanced']);
        $this->assertEquals('Hari kerja selesai target bonus tercapai', $array['closing_notes']);
    }

    public function test_work_day_resource_indicates_unbalanced_when_needs_reconciliation(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-15',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
            'total_orders' => 5,
        ]);

        $resource = new WorkDayResource($workDay);
        $array = $resource->toArray(Request::create('/api/v1/work-days/' . $workDay->id));

        $this->assertFalse($array['is_balanced']);
        $this->assertEquals(5, $array['total_trips']);
        $this->assertEquals(0, $array['total_shopee_income']);
    }
}
