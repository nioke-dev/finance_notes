<?php

namespace Tests\Feature\Web;

use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\Transaction;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletManagementWebTest extends TestCase
{
    use RefreshDatabase;

    protected WorkDay $workDay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);
    }

    public function test_wallets_index_page_loads_with_active_and_archived_accounts(): void
    {
        // Tutup salah satu akun
        $gopayMerchant = Account::where('name', 'GoPay Merchant')->firstOrFail();
        $gopayMerchant->update(['is_active' => false]);

        $response = $this->get('/wallets');

        $response->assertStatus(200);
        $response->assertSee('Manajemen Wadah Finansial');
        $response->assertSee('BCA');
        $response->assertSee('GoPay Merchant');
        $response->assertSee('Wadah Nonaktif / Sedang Ditutup');
    }

    public function test_user_can_delete_unpaid_debt(): void
    {
        $debt = Debt::create([
            'name' => 'Salah Catat Tagihan SPayLater',
            'platform' => 'spaylater',
            'borrower_name' => 'Self',
            'principal_amount' => 150000,
            'fee_amount' => 0,
            'total_obligation' => 150000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(10)->toDateString(),
            'amount_due' => 150000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->delete('/operational/debts/' . $debt->id);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNull(Debt::find($debt->id));
        $this->assertDatabaseMissing('debt_installments', ['debt_id' => $debt->id]);
    }

    public function test_user_cannot_delete_debt_with_paid_installments(): void
    {
        $debt = Debt::create([
            'name' => 'Tagihan Sedang Dicicil',
            'platform' => 'tiktok_paylater',
            'borrower_name' => 'Self',
            'principal_amount' => 300000,
            'fee_amount' => 0,
            'total_obligation' => 300000,
            'tenor_months' => 2,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->subDays(5)->toDateString(),
            'amount_due' => 150000,
            'amount_paid' => 150000,
            'status' => 'paid',
        ]);

        DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 2,
            'due_date' => now()->addDays(25)->toDateString(),
            'amount_due' => 150000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->delete('/operational/debts/' . $debt->id);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull(Debt::find($debt->id));
    }

    public function test_user_cannot_delete_cash_loan_with_disbursed_funds(): void
    {
        $debt = Debt::create([
            'name' => 'Pinjaman Cair ke Rekening',
            'platform' => 'spinjam',
            'borrower_name' => 'Self',
            'principal_amount' => 1000000,
            'fee_amount' => 50000,
            'total_obligation' => 1050000,
            'tenor_months' => 3,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        Transaction::create([
            'work_day_id' => $this->workDay->id,
            'type' => 'income',
            'amount' => 1000000,
            'description' => 'Pencairan Pinjaman',
            'transaction_time' => now(),
            'metadata' => [
                'type' => 'loan_disbursement',
                'debt_id' => $debt->id,
            ],
        ]);

        $response = $this->delete('/operational/debts/' . $debt->id);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull(Debt::find($debt->id));
    }
}
