<?php

namespace Tests\Unit\Models;

use App\Models\Debt;
use App\Models\DebtInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DebtInstallmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_debt_installments_table_has_all_required_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('debt_installments', [
                'id',
                'debt_id',
                'installment_number',
                'due_date',
                'amount_due',
                'amount_paid',
                'status',
                'paid_at',
                'created_at',
                'updated_at',
            ])
        );
    }

    public function test_installment_belongs_to_debt(): void
    {
        $debt = Debt::create([
            'name' => 'SPayLater HP Rofik',
            'platform' => 'spaylater',
            'borrower_name' => 'Rofik',
            'principal_amount' => 1200000,
            'fee_amount' => 60000,
            'total_obligation' => 1260000,
            'tenor_months' => 3,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $installment = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => '2026-11-05',
            'amount_due' => 420000,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'paid_at' => null,
        ]);

        $this->assertInstanceOf(Debt::class, $installment->debt);
        $this->assertEquals($debt->id, $installment->debt->id);
        $this->assertFalse($installment->isPaid());
    }

    public function test_scope_unpaid_filters_only_unpaid_installments(): void
    {
        $debt = Debt::create([
            'name' => 'SeaBank Pinjam',
            'platform' => 'seabank_pinjam',
            'principal_amount' => 3000000,
            'fee_amount' => 150000,
            'total_obligation' => 3150000,
            'tenor_months' => 3,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $unpaidInstallment = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => '2026-11-10',
            'amount_due' => 1050000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $paidInstallment = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 2,
            'due_date' => '2026-12-10',
            'amount_due' => 1050000,
            'amount_paid' => 1050000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $unpaidList = DebtInstallment::unpaid()->get();
        $this->assertCount(1, $unpaidList);
        $this->assertEquals($unpaidInstallment->id, $unpaidList->first()->id);

        $paidList = DebtInstallment::paid()->get();
        $this->assertCount(1, $paidList);
        $this->assertEquals($paidInstallment->id, $paidList->first()->id);
        $this->assertTrue($paidInstallment->isPaid());
    }
}
