<?php

namespace Tests\Feature\Services;

use App\Exceptions\UnresolvedDiscrepancyException;
use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;
use App\Services\ClosingDayService;
use App\Services\ReconciliationEngineService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ClosingDayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClosingDayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = new ClosingDayService(new ReconciliationEngineService());
    }

    public function test_cannot_finalize_work_day_if_discrepancy_exists(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::where('name', 'Cash')->first();
        $cash->update(['current_balance' => 100000]);

        // Fisik aktual Rp99.500 (selisih -500)
        CashDenomination::create([
            'work_day_id' => $workDay->id,
            'bill_50000' => 1,
            'bill_20000' => 2,
            'bill_5000' => 1,
            'bill_2000' => 2,
            'coin_500' => 1,
        ]);

        try {
            $this->service->finalizeWorkDay($workDay, []);
            $this->fail('Expected UnresolvedDiscrepancyException was not thrown.');
        } catch (UnresolvedDiscrepancyException $e) {
            $this->assertNotEmpty($e->getDiscrepantAccounts());
            $this->assertEquals(-500, $e->getDiscrepantAccounts()[0]['discrepancy']);
        }

        $this->assertEquals(WorkDay::STATUS_NEEDS_RECONCILIATION, $workDay->fresh()->status);
        $this->assertNull($workDay->fresh()->closed_at);
    }

    public function test_successfully_finalizes_work_day_when_discrepancy_is_zero(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::where('name', 'Cash')->first();
        $cash->update(['current_balance' => 100000]);

        CashDenomination::create([
            'work_day_id' => $workDay->id,
            'bill_100000' => 1,
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 500000]);

        $digitalBalances = [
            ['account_id' => $bca->id, 'actual_balance' => 500000],
        ];

        $finalized = $this->service->finalizeWorkDay($workDay, $digitalBalances, 'Tutup shift lancar');

        $this->assertEquals(WorkDay::STATUS_FINAL, $finalized->status);
        $this->assertTrue($finalized->isFinal());
        $this->assertNotNull($finalized->closed_at);
        $this->assertEquals('Tutup shift lancar', $finalized->closing_notes);
    }

    public function test_cannot_finalize_already_finalized_work_day(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_FINAL,
            'closed_at' => now(),
        ]);

        $this->service->finalizeWorkDay($workDay, []);
    }
}
