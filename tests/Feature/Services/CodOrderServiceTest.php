<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\WorkDay;
use App\Services\CodOrderService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CodOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CodOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = app(CodOrderService::class);
    }

    public function test_cod_exact_amount_records_zero_surplus(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 100000]);

        $shopeepayAccount = Account::where('name', 'ShopeePay')->first();
        $shopeepayAccount->update(['current_balance' => 200000]);

        $tipIncomeAccount = Account::where('name', 'Tip Cash / Surplus COD')->first();
        $tipIncomeAccount->update(['current_balance' => 0]);

        $transaction = $this->service->recordCodOrder(
            workDay: $workDay,
            billAmount: 20000,
            receivedAmount: 20000,
            orderNumber: 'SP-1001'
        );

        $this->assertEquals(20000, $transaction->amount);
        $this->assertEquals('income', $transaction->type);
        $this->assertEquals(0, $transaction->metadata['surplus_amount']);

        $cashAccount->refresh();
        $this->assertEquals(120000, $cashAccount->current_balance);

        $shopeepayAccount->refresh();
        $this->assertEquals(180000, $shopeepayAccount->current_balance);

        $tipIncomeAccount->refresh();
        $this->assertEquals(0, $tipIncomeAccount->current_balance);
    }

    public function test_cod_with_extra_cash_allocates_difference_to_tip_income_account(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 100000]);

        $shopeepayAccount = Account::where('name', 'ShopeePay')->first();
        $shopeepayAccount->update(['current_balance' => 200000]);

        $tipIncomeAccount = Account::where('name', 'Tip Cash / Surplus COD')->first();
        $tipIncomeAccount->update(['current_balance' => 0]);

        // Tagihan 15.125, uang fisik diterima 16.000 -> Surplus 875
        $transaction = $this->service->recordCodOrder(
            workDay: $workDay,
            billAmount: 15125,
            receivedAmount: 16000,
            orderNumber: 'SP-1002'
        );

        $this->assertEquals(16000, $transaction->amount);
        $this->assertEquals(875, $transaction->metadata['surplus_amount']);

        $cashAccount->refresh();
        $this->assertEquals(116000, $cashAccount->current_balance);

        $shopeepayAccount->refresh();
        $this->assertEquals(200000 - 15125, $shopeepayAccount->current_balance);

        $tipIncomeAccount->refresh();
        $this->assertEquals(875, $tipIncomeAccount->current_balance);
    }

    public function test_cod_fails_if_received_amount_less_than_bill(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uang tunai diterima tidak boleh kurang dari nominal tagihan COD.');

        $this->service->recordCodOrder(
            workDay: $workDay,
            billAmount: 20000,
            receivedAmount: 15000
        );
    }
}
