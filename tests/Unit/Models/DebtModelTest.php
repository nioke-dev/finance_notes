<?php

namespace Tests\Unit\Models;

use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DebtModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_debts_table_has_all_required_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('debts', [
                'id',
                'name',
                'platform',
                'borrower_name',
                'principal_amount',
                'fee_amount',
                'total_obligation',
                'tenor_months',
                'status',
                'started_at',
                'created_at',
                'updated_at',
            ])
        );
    }

    public function test_debt_status_helpers_and_scopes(): void
    {
        $activeDebt = Debt::create([
            'name' => 'SPayLater - Beli Sepatu',
            'platform' => 'spaylater',
            'borrower_name' => null,
            'principal_amount' => 500000,
            'fee_amount' => 25000,
            'total_obligation' => 525000,
            'tenor_months' => 3,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $paidDebt = Debt::create([
            'name' => 'Talangan Rofik',
            'platform' => 'person',
            'borrower_name' => 'Rofik',
            'principal_amount' => 150000,
            'fee_amount' => 0,
            'total_obligation' => 150000,
            'tenor_months' => 1,
            'status' => 'paid_off',
            'started_at' => '2026-10-01',
        ]);

        $this->assertFalse($activeDebt->isPaidOff());
        $this->assertTrue($paidDebt->isPaidOff());

        $activeDebts = Debt::active()->get();
        $this->assertCount(1, $activeDebts);
        $this->assertEquals($activeDebt->id, $activeDebts->first()->id);

        $paidDebts = Debt::paidOff()->get();
        $this->assertCount(1, $paidDebts);
        $this->assertEquals($paidDebt->id, $paidDebts->first()->id);
    }
}
