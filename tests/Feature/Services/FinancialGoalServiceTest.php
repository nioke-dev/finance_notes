<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\FinancialGoal;
use App\Models\WorkDay;
use App\Services\FinancialGoalService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialGoalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialGoalService $service;
    protected WorkDay $workDay;
    protected Account $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DefaultAccountsSeeder::class);

        $this->cash = Account::where('name', 'Cash')->firstOrFail();
        $this->cash->update(['current_balance' => 200000, 'initial_balance' => 200000]);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $this->service = app(FinancialGoalService::class);
    }

    public function test_create_goal_and_allocate_funds(): void
    {
        $goal = $this->service->createGoal([
            'name' => 'Ganti Oli Mesin + Gardan',
            'category' => 'work_essential',
            'priority_level' => 3, // Nyawa Narik
            'target_amount' => 65000,
            'target_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertEquals('Ganti Oli Mesin + Gardan', $goal->name);
        $this->assertEquals(3, $goal->priority_level);
        $this->assertEquals(0, $goal->allocated_amount);
        $this->assertEquals(0, $goal->progress_percent);

        // Sisihkan uang bertahap 20.000
        $this->service->allocateFunds($goal, 20000, 'Hasil narik shift sore');
        $this->assertEquals(20000, $goal->fresh()->allocated_amount);
        $this->assertEquals(31, $goal->fresh()->progress_percent);
        $this->assertEquals(45000, $goal->fresh()->remaining_amount);
    }

    public function test_calculate_safe_to_spend(): void
    {
        // Total uang likuid: Rp200.000
        // Goal Level 1 (Beras): Rp50.000 (sudah disisihkan 50.000)
        $this->service->createGoal([
            'name' => 'Beras 5kg',
            'category' => 'pangan',
            'priority_level' => 1,
            'target_amount' => 70000,
        ]);
        $g1 = FinancialGoal::first();
        $this->service->allocateFunds($g1, 50000);

        // Goal Level 3 (Oli Mesin): Rp65.000 (sudah disisihkan 30.000)
        $g3 = $this->service->createGoal([
            'name' => 'Oli Mesin',
            'category' => 'work_essential',
            'priority_level' => 3,
            'target_amount' => 65000,
        ]);
        $this->service->allocateFunds($g3, 30000);

        // Total survival reserved = 50.000 + 30.000 = 80.000
        // Safe-to-spend = 200.000 - 80.000 = 120.000
        $calc = $this->service->calculateSafeToSpend(200000);

        $this->assertEquals(200000, $calc['total_liquid']);
        $this->assertEquals(80000, $calc['total_survival_reserved']);
        $this->assertEquals(120000, $calc['safe_to_spend']);
    }

    public function test_achieve_goal_records_expense_and_completes_goal(): void
    {
        $goal = $this->service->createGoal([
            'name' => 'Ganti Oli Mesin',
            'category' => 'maintenance',
            'priority_level' => 3,
            'target_amount' => 60000,
        ]);

        $this->service->allocateFunds($goal, 60000);

        // Belanja di bengkel bayar pakai Cash
        $achieved = $this->service->achieveGoal(
            goal: $goal,
            paymentAccount: $this->cash,
            actualSpentAmount: 60000,
            workDay: $this->workDay
        );

        $this->assertEquals('achieved', $achieved->status);
        $this->assertNotNull($achieved->achieved_at);

        // Kas berkurang 60.000: 200.000 - 60.000 = 140.000
        $this->assertEquals(140000, $this->cash->fresh()->current_balance);

        // Beban Perawatan Motor didebit 60.000
        $expenseAcc = Account::where('name', 'Beban Perawatan & Servis Motor')->firstOrFail();
        $this->assertEquals(60000, $expenseAcc->current_balance);
    }
}
