<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\WorkDay;
use App\Services\AutoQrisTipService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoQrisTipServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AutoQrisTipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = app(AutoQrisTipService::class);
    }

    public function test_auto_records_income_when_gopay_balance_increases(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $gopayAccount = Account::where('name', 'GoPay')->first();
        $gopayAccount->update(['current_balance' => 100000]);

        $merchantAccount = Account::where('name', 'GoPay Merchant')->first();
        $merchantAccount->update(['current_balance' => 0]);

        $transactions = $this->service->detectAndRecordBalanceIncreases(
            workDay: $workDay,
            actualGoPayBalance: 115000,
            actualMerchantBalance: 0
        );

        $this->assertCount(1, $transactions);
        $this->assertEquals(15000, $transactions[0]->amount);
        $this->assertEquals('Pendapatan QRIS / Tip Lapangan (Auto-detected via GoPay)', $transactions[0]->description);

        $gopayAccount->refresh();
        $this->assertEquals(115000, $gopayAccount->current_balance);

        $tipIncomeAccount = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->first();
        $this->assertEquals(15000, $tipIncomeAccount->current_balance);
    }

    public function test_auto_records_income_when_merchant_balance_increases(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $gopayAccount = Account::where('name', 'GoPay')->first();
        $gopayAccount->update(['current_balance' => 50000]);

        $merchantAccount = Account::where('name', 'GoPay Merchant')->first();
        $merchantAccount->update(['current_balance' => 0]);

        $transactions = $this->service->detectAndRecordBalanceIncreases(
            workDay: $workDay,
            actualGoPayBalance: 50000,
            actualMerchantBalance: 5000
        );

        $this->assertCount(1, $transactions);
        $this->assertEquals(5000, $transactions[0]->amount);
        $this->assertEquals('Pendapatan QRIS / Tip Lapangan (Auto-detected via GoPay Merchant)', $transactions[0]->description);

        $merchantAccount->refresh();
        $this->assertEquals(5000, $merchantAccount->current_balance);

        $tipIncomeAccount = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->first();
        $this->assertEquals(5000, $tipIncomeAccount->current_balance);
    }

    public function test_does_not_create_transaction_when_balances_are_unchanged(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $gopayAccount = Account::where('name', 'GoPay')->first();
        $gopayAccount->update(['current_balance' => 50000]);

        $merchantAccount = Account::where('name', 'GoPay Merchant')->first();
        $merchantAccount->update(['current_balance' => 10000]);

        $transactions = $this->service->detectAndRecordBalanceIncreases(
            workDay: $workDay,
            actualGoPayBalance: 50000,
            actualMerchantBalance: 10000
        );

        $this->assertEmpty($transactions);

        // Even when actual is less (e.g. spent or transferred out), do not auto-record tip
        $transactionsLess = $this->service->detectAndRecordBalanceIncreases(
            workDay: $workDay,
            actualGoPayBalance: 40000,
            actualMerchantBalance: 5000
        );

        $this->assertEmpty($transactionsLess);
    }
}
