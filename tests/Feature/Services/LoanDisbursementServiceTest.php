<?php

namespace Tests\Feature\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use App\Services\LedgerService;
use App\Services\LoanDisbursementService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LoanDisbursementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LoanDisbursementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = new LoanDisbursementService(new LedgerService());
    }

    public function test_loan_disbursement_credits_net_amount_to_destination_account(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 100000]);

        $debt = Debt::create([
            'name' => 'GoPay Pinjam Modal Darurat',
            'platform' => 'gopay_pinjam',
            'principal_amount' => 500000,
            'fee_amount' => 100000,
            'total_obligation' => 600000,
            'tenor_months' => 3,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $result = $this->service->disburseLoan(
            workDay: $workDay,
            debt: $debt,
            destinationAccount: $bca,
            netReceivedAmount: 500000,
            feeAmount: 100000,
            disbursedAt: now(),
            dueDayOfMonth: 5
        );

        $this->assertEquals(600000, $bca->fresh()->current_balance); // 100k awal + 500k dana bersih
        $this->assertCount(3, $result->installments);

        $adminExpense = Account::where('name', 'Beban Biaya Admin & Provisi Pinjaman')->first();
        $this->assertEquals(100000, $adminExpense->current_balance);

        $debtLiability = Account::where('name', 'Kewajiban Utang - ' . $debt->name)->first();
        $this->assertEquals(600000, $debtLiability->current_balance);
    }

    public function test_loan_disbursement_creates_exact_number_of_installments(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();

        $debt = Debt::create([
            'name' => 'SeaBank Pinjam 6 Bulan',
            'platform' => 'seabank_pinjam',
            'principal_amount' => 1200000,
            'fee_amount' => 0,
            'total_obligation' => 1200000,
            'tenor_months' => 6,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $result = $this->service->disburseLoan(
            workDay: $workDay,
            debt: $debt,
            destinationAccount: $bca,
            netReceivedAmount: 1200000,
            feeAmount: 0,
            disbursedAt: now(),
            dueDayOfMonth: 10
        );

        $this->assertCount(6, $result->installments);
        $this->assertEquals(200000, $result->installments->first()->amount_due);
        $this->assertEquals(0, $result->installments->first()->amount_paid);
        $this->assertEquals('unpaid', $result->installments->first()->status);
    }

    public function test_mismatched_amounts_throw_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();

        $debt = Debt::create([
            'name' => 'Pinjaman Mismatch',
            'platform' => 'other',
            'principal_amount' => 500000,
            'fee_amount' => 50000,
            'total_obligation' => 550000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $this->service->disburseLoan(
            workDay: $workDay,
            debt: $debt,
            destinationAccount: $bca,
            netReceivedAmount: 400000, // 400k + 50k != 550k
            feeAmount: 50000,
            disbursedAt: now(),
            dueDayOfMonth: 5
        );
    }
}
