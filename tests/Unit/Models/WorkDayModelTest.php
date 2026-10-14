<?php

namespace Tests\Unit\Models;

use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorkDayModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_day_status_helpers_return_correct_boolean(): void
    {
        $pendingWorkDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $finalWorkDay = WorkDay::create([
            'work_date' => '2026-10-15',
            'status' => WorkDay::STATUS_FINAL,
        ]);

        $this->assertTrue($pendingWorkDay->needsReconciliation());
        $this->assertFalse($pendingWorkDay->isFinal());

        $this->assertTrue($finalWorkDay->isFinal());
        $this->assertFalse($finalWorkDay->needsReconciliation());
    }

    public function test_scope_unfinalized_filters_only_pending_work_days(): void
    {
        WorkDay::create([
            'work_date' => '2026-10-10',
            'status' => WorkDay::STATUS_FINAL,
        ]);

        WorkDay::create([
            'work_date' => '2026-10-11',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        WorkDay::create([
            'work_date' => '2026-10-12',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $unfinalized = WorkDay::unfinalized()->get();
        $finalized = WorkDay::finalized()->get();

        $this->assertCount(2, $unfinalized);
        $this->assertCount(1, $finalized);
    }

    public function test_work_date_casts_to_carbon_date_instance(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'opened_at' => '2026-10-14 07:30:00',
            'closed_at' => '2026-10-14 21:00:00',
            'total_orders' => 18,
            'online_hours' => 10.5,
            'is_performance_eligible' => true,
        ]);

        $this->assertInstanceOf(Carbon::class, $workDay->work_date);
        $this->assertInstanceOf(Carbon::class, $workDay->opened_at);
        $this->assertInstanceOf(Carbon::class, $workDay->closed_at);
        $this->assertSame(18, $workDay->total_orders);
        $this->assertTrue($workDay->is_performance_eligible);
    }
}
