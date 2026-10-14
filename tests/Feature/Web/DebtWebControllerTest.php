<?php

namespace Tests\Feature\Web;

use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtWebControllerTest extends TestCase
{
    use RefreshDatabase;

    protected WorkDay $workDay;
    protected Account $bcaAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $this->bcaAccount = Account::where('name', 'BCA')->firstOrFail();
        $this->bcaAccount->update([
            'current_balance' => 1000000,
            'initial_balance' => 1000000,
        ]);
    }

    public function test_debts_index_displays_active_debts_and_summary_totals(): void
    {
        // Setup PayLater Debt
        $paylater = Debt::create([
            'name' => 'Belanja Shopee SPayLater: Jas Hujan',
            'platform' => 'spaylater',
            'borrower_name' => 'Self',
            'principal_amount' => 500000,
            'fee_amount' => 0,
            'total_obligation' => 500000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        DebtInstallment::create([
            'debt_id' => $paylater->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(5)->toDateString(),
            'amount_due' => 500000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        // Setup Cash Loan (SPinjam)
        $cashLoan = Debt::create([
            'name' => 'SPinjam Modal Tambahan',
            'platform' => 'spinjam',
            'borrower_name' => 'Self',
            'principal_amount' => 1000000,
            'fee_amount' => 50000,
            'total_obligation' => 1050000,
            'tenor_months' => 3,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        DebtInstallment::create([
            'debt_id' => $cashLoan->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(15)->toDateString(),
            'amount_due' => 350000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        // Setup Piutang Teman
        $friendDebt = Debt::create([
            'name' => 'Talangan Bensin Teman',
            'platform' => 'person',
            'borrower_name' => 'Rofik',
            'principal_amount' => 100000,
            'fee_amount' => 0,
            'total_obligation' => 100000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        DebtInstallment::create([
            'debt_id' => $friendDebt->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(7)->toDateString(),
            'amount_due' => 100000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->get('/debts');

        $response->assertStatus(200);
        $response->assertViewIs('pages.debts.index');
        $response->assertSee('Tagihan PayLater (Belanja)');
        $response->assertSee('Pinjaman Tunai / KTA');
        $response->assertSee('Piutang Kas ke Teman');
        $response->assertSee('Belanja Shopee SPayLater: Jas Hujan');
        $response->assertSee('SPinjam Modal Tambahan');
        $response->assertSee('Rofik');
        $response->assertSee('500.000');
        $response->assertSee('1.050.000');
        $response->assertSee('100.000');
    }

    public function test_user_can_record_tiktok_paylater_purchase(): void
    {
        $response = $this->post('/operational/spaylater-purchase', [
            'work_day_id' => $this->workDay->id,
            'platform' => 'tiktok_paylater',
            'item_name' => 'Sepatu Kerja Anti Slip',
            'total_price' => 240000,
            'tenor_months' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $debt = Debt::where('platform', 'tiktok_paylater')->first();
        $this->assertNotNull($debt);
        $this->assertEquals(240000, $debt->principal_amount);
        $this->assertEquals(1, $debt->tenor_months);
        $this->assertTrue($debt->isPayLater());
        $this->assertFalse($debt->isCashLoan());
        $this->assertEquals('TikTok PayLater', $debt->getPlatformLabel());

        $installments = $debt->installments;
        $this->assertCount(1, $installments);
        $this->assertEquals(240000, $installments->first()->amount_due);
    }

    public function test_user_can_record_spinjam_cash_loan_disbursement(): void
    {
        $response = $this->post('/operational/debt', [
            'work_day_id' => $this->workDay->id,
            'name' => 'SPinjam Pencairan Dana',
            'platform' => 'spinjam',
            'principal_amount' => 1000000,
            'fee_amount' => 60000,
            'tenor_months' => 3,
            'destination_account_id' => $this->bcaAccount->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $debt = Debt::where('platform', 'spinjam')->first();
        $this->assertNotNull($debt);
        $this->assertEquals(1000000, $debt->principal_amount);
        $this->assertEquals(60000, $debt->fee_amount);
        $this->assertEquals(1060000, $debt->total_obligation);
        $this->assertTrue($debt->isCashLoan());
        $this->assertFalse($debt->isPayLater());
        $this->assertEquals('Shopee Pinjam (SPinjam)', $debt->getPlatformLabel());

        // Dana bersih 1.000.000 masuk ke BCA (1.000.000 awal + 1.000.000 = 2.000.000)
        $this->assertEquals(2000000, $this->bcaAccount->fresh()->current_balance);
    }
}
