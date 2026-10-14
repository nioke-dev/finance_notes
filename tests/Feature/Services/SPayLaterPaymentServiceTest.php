<?php

namespace Tests\Feature\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\WorkDay;
use App\Services\LedgerService;
use App\Services\SPayLaterPaymentService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SPayLaterPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SPayLaterPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = new SPayLaterPaymentService(new LedgerService());
    }

    public function test_pay_installment_with_promo_discount_balances_ledger(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $seabank = Account::where('name', 'SeaBank')->first();
        $seabank->update(['current_balance' => 500000]);

        $debt = Debt::create([
            'name' => 'SPayLater Belanja Sepatu',
            'platform' => 'spaylater',
            'principal_amount' => 500000,
            'fee_amount' => 0,
            'total_obligation' => 500000,
            'tenor_months' => 2,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $liabilityAccount = Account::create([
            'name' => 'Kewajiban Utang - ' . $debt->name,
            'type' => AccountType::DEBT,
            'current_balance' => 500000,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $installment1 = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => '2026-11-05',
            'amount_due' => 250000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $installment2 = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 2,
            'due_date' => '2026-12-05',
            'amount_due' => 250000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        // Bayar 200k kas + 50k voucher diskon
        $transaction = $this->service->payInstallment(
            workDay: $workDay,
            installment: $installment1,
            paymentAccount: $seabank,
            actualPaidAmount: 200000,
            promoDiscountAmount: 50000
        );

        $this->assertNotNull($transaction);
        $this->assertEquals(300000, $seabank->fresh()->current_balance); // 500k - 200k
        $this->assertEquals(250000, $liabilityAccount->fresh()->current_balance); // 500k - 250k

        $discountIncome = Account::where('name', 'Pendapatan Diskon / Promo Finansial')->first();
        $this->assertEquals(50000, $discountIncome->current_balance);

        $this->assertTrue($installment1->fresh()->isPaid());
        $this->assertEquals('paid', $installment1->fresh()->status);
        $this->assertEquals(200000, $installment1->fresh()->amount_paid);
        $this->assertEquals('active', $debt->fresh()->status); // Masih ada cicilan #2 belum bayar
    }

    public function test_paying_last_installment_updates_parent_debt_status_to_paid_off(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 300000]);

        $debt = Debt::create([
            'name' => 'SPayLater 1 Bulan',
            'platform' => 'spaylater',
            'principal_amount' => 100000,
            'fee_amount' => 0,
            'total_obligation' => 100000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        Account::create([
            'name' => 'Kewajiban Utang - ' . $debt->name,
            'type' => AccountType::DEBT,
            'current_balance' => 100000,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $installment = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => '2026-11-05',
            'amount_due' => 100000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $this->service->payInstallment(
            workDay: $workDay,
            installment: $installment,
            paymentAccount: $bca,
            actualPaidAmount: 100000,
            promoDiscountAmount: 0
        );

        $this->assertEquals('paid_off', $debt->fresh()->status);
        $this->assertTrue($debt->fresh()->isPaidOff());
    }

    public function test_cannot_pay_already_paid_installment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();

        $debt = Debt::create([
            'name' => 'SPayLater Lunas',
            'platform' => 'spaylater',
            'principal_amount' => 50000,
            'fee_amount' => 0,
            'total_obligation' => 50000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $installment = DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => '2026-11-05',
            'amount_due' => 50000,
            'amount_paid' => 50000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->service->payInstallment(
            workDay: $workDay,
            installment: $installment,
            paymentAccount: $bca,
            actualPaidAmount: 50000,
            promoDiscountAmount: 0
        );
    }
}
