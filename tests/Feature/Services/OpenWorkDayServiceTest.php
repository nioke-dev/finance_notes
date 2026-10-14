<?php

namespace Tests\Feature\Services;

use App\Models\WorkDay;
use App\Services\OpenWorkDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OpenWorkDayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OpenWorkDayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpenWorkDayService();
    }

    public function test_can_open_new_work_day_when_previous_day_is_unfinalized(): void
    {
        // Hari kemarin berstatus needs_reconciliation (belum selesai closing / selisih)
        $yesterday = WorkDay::create([
            'work_date' => '2026-10-13',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
            'opened_at' => now()->subDay(),
        ]);

        $todayCarbon = Carbon::parse('2026-10-14 07:00:00');
        $newWorkDay = $this->service->openWorkDay($todayCarbon);

        $this->assertNotNull($newWorkDay);
        $this->assertEquals('2026-10-14', $newWorkDay->work_date->toDateString());
        $this->assertEquals(WorkDay::STATUS_NEEDS_RECONCILIATION, $newWorkDay->status);
        $this->assertNull($newWorkDay->closed_at);
        $this->assertEquals(WorkDay::STATUS_NEEDS_RECONCILIATION, $yesterday->fresh()->status);
    }

    public function test_open_work_day_is_idempotent_for_same_date(): void
    {
        $todayCarbon = Carbon::parse('2026-10-14 07:00:00');
        
        $firstCall = $this->service->openWorkDay($todayCarbon);
        $secondCall = $this->service->openWorkDay($todayCarbon);

        $this->assertEquals($firstCall->id, $secondCall->id);
        $this->assertEquals(1, WorkDay::whereDate('work_date', '2026-10-14')->count());
    }
}
