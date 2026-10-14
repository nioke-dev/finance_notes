<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;
use App\Services\ReconciliationEngineService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReconciliationEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = new ReconciliationEngineService();
    }

    public function test_reconciliation_detects_exact_cash_shortage(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cash = Account::where('name', 'Cash')->first();
        $cash->update(['current_balance' => 350000]);

        // Fisik aktual Rp335.000 (Shortage Rp15.000)
        CashDenomination::create([
            'work_day_id' => $workDay->id,
            'bill_100000' => 3, // 300.000
            'bill_20000' => 1,  // 20.000
            'bill_10000' => 1,  // 10.000
            'bill_5000' => 1,   // 5.000
        ]);

        $report = $this->service->reconcileWorkDay($workDay, []);

        $this->assertFalse($report['is_balanced']);
        $this->assertEquals(15000, $report['total_absolute_discrepancy']);

        $cashReport = collect($report['accounts'])->firstWhere('account_name', 'Cash');
        $this->assertEquals(-15000, $cashReport['discrepancy']);
        $this->assertEquals('shortage', $cashReport['status']);
    }

    public function test_reconciliation_detects_digital_surplus(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 1900000]);

        $digitalActualBalances = [
            ['account_id' => $bca->id, 'actual_balance' => 2000000], // Surplus 100k
        ];

        $report = $this->service->reconcileWorkDay($workDay, $digitalActualBalances);

        $this->assertFalse($report['is_balanced']);
        $this->assertEquals(100000, $report['total_absolute_discrepancy']);

        $bcaReport = collect($report['accounts'])->firstWhere('account_name', 'BCA');
        $this->assertEquals(100000, $bcaReport['discrepancy']);
        $this->assertEquals('surplus', $bcaReport['status']);
    }

    public function test_reconciliation_reports_balanced_when_all_accounts_match_zero(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cash = Account::where('name', 'Cash')->first();
        $cash->update(['current_balance' => 100000]);

        CashDenomination::create([
            'work_day_id' => $workDay->id,
            'bill_100000' => 1,
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 500000]);

        $digitalActualBalances = [
            ['account_id' => $bca->id, 'actual_balance' => 500000],
        ];

        $report = $this->service->reconcileWorkDay($workDay, $digitalActualBalances);

        $this->assertTrue($report['is_balanced']);
        $this->assertEquals(0, $report['total_absolute_discrepancy']);

        foreach ($report['accounts'] as $acc) {
            $this->assertEquals(0, $acc['discrepancy']);
            $this->assertEquals('matched', $acc['status']);
        }
    }
}
