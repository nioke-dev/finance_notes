<?php

namespace Tests\Feature\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use App\Services\FriendDebtSettlementService;
use App\Services\LedgerService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FriendDebtSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FriendDebtSettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = new FriendDebtSettlementService(new LedgerService());
    }

    public function test_settle_with_forgiveness_closes_debt_and_records_charity_expense(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 500000]);

        $debt = Debt::create([
            'name' => 'Talangan Makan Rofik',
            'platform' => 'person',
            'borrower_name' => 'Rofik',
            'principal_amount' => 249000,
            'fee_amount' => 0,
            'total_obligation' => 249000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $receivableAccount = Account::create([
            'name' => 'Piutang Teman - Rofik',
            'type' => AccountType::RECEIVABLE,
            'current_balance' => 249000,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $transaction = $this->service->settleFriendDebt(
            workDay: $workDay,
            debt: $debt,
            receivingAccount: $bca,
            receivedAmount: 248000,
            forgiveRemainder: true
        );

        $this->assertNotNull($transaction);
        $this->assertEquals(748000, $bca->fresh()->current_balance); // 500k + 248k
        $this->assertEquals(0, $receivableAccount->fresh()->current_balance); // 249k - 249k lunas

        $charityExpense = Account::where('name', 'Beban Sedekah & Bantuan Pribadi')->first();
        $this->assertEquals(1000, $charityExpense->current_balance);

        $this->assertEquals('paid_off', $debt->fresh()->status);
        $this->assertTrue($debt->fresh()->isPaidOff());
    }

    public function test_settle_partial_without_forgiveness_keeps_debt_active(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 100000]);

        $debt = Debt::create([
            'name' => 'Talangan Arno',
            'platform' => 'person',
            'borrower_name' => 'Arno',
            'principal_amount' => 249000,
            'fee_amount' => 0,
            'total_obligation' => 249000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $receivableAccount = Account::create([
            'name' => 'Piutang Teman - Arno',
            'type' => AccountType::RECEIVABLE,
            'current_balance' => 249000,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $this->service->settleFriendDebt(
            workDay: $workDay,
            debt: $debt,
            receivingAccount: $bca,
            receivedAmount: 200000,
            forgiveRemainder: false
        );

        $this->assertEquals(300000, $bca->fresh()->current_balance); // 100k + 200k
        $this->assertEquals(49000, $receivableAccount->fresh()->current_balance); // 249k - 200k = sisa 49k
        $this->assertEquals('active', $debt->fresh()->status);
        $this->assertFalse($debt->fresh()->isPaidOff());

        $charityExpense = Account::where('name', 'Beban Sedekah & Bantuan Pribadi')->first();
        $this->assertEquals(0, $charityExpense->current_balance);
    }

    public function test_settle_fails_if_received_amount_exceeds_obligation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();

        $debt = Debt::create([
            'name' => 'Talangan Rafsan',
            'platform' => 'person',
            'borrower_name' => 'Rafsan',
            'principal_amount' => 50000,
            'fee_amount' => 0,
            'total_obligation' => 50000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $this->service->settleFriendDebt(
            workDay: $workDay,
            debt: $debt,
            receivingAccount: $bca,
            receivedAmount: 60000, // Lebih besar dari kewajiban
            forgiveRemainder: false
        );
    }
}
