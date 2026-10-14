<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\WorkDay;
use App\Services\MerchantSettlementService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MerchantSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = app(MerchantSettlementService::class);
    }

    public function test_settlement_does_not_execute_when_amount_under_10k(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $merchantAccount = Account::where('name', 'GoPay Merchant')->first();
        $merchantAccount->update(['current_balance' => 8500]);

        $gopayAccount = Account::where('name', 'GoPay')->first();
        $gopayAccount->update(['current_balance' => 50000]);

        $transaction = $this->service->processSettlementIfEligible(
            workDay: $workDay,
            amountToSettle: 8500
        );

        $this->assertNull($transaction);

        $merchantAccount->refresh();
        $this->assertEquals(8500, $merchantAccount->current_balance);

        $gopayAccount->refresh();
        $this->assertEquals(50000, $gopayAccount->current_balance);
    }

    public function test_settlement_executes_successfully_when_amount_is_10k_or_more(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $merchantAccount = Account::where('name', 'GoPay Merchant')->first();
        $merchantAccount->update(['current_balance' => 15000]);

        $gopayAccount = Account::where('name', 'GoPay')->first();
        $gopayAccount->update(['current_balance' => 50000]);

        $transaction = $this->service->processSettlementIfEligible(
            workDay: $workDay,
            amountToSettle: 15000
        );

        $this->assertNotNull($transaction);
        $this->assertEquals(15000, $transaction->amount);
        $this->assertEquals('transfer', $transaction->type);

        $merchantAccount->refresh();
        $this->assertEquals(0, $merchantAccount->current_balance);

        $gopayAccount->refresh();
        $this->assertEquals(65000, $gopayAccount->current_balance);
    }
}
