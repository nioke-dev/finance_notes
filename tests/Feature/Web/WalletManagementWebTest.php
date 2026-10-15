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

    public function test_user_can_create_new_paylater_provider(): void
    {
        $response = $this->post('/operational/paylater-providers', [
            'name' => 'Blibli PayLater',
            'credit_limit' => 2500000,
            'icon' => '🛍️',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('paylater_providers', [
            'name' => 'Blibli PayLater',
            'slug' => 'blibli_paylater',
            'credit_limit' => 2500000,
            'is_active' => true,
        ]);
    }

    public function test_user_can_update_paylater_provider_credit_limit(): void
    {
        $provider = \App\Models\PaylaterProvider::create([
            'name' => 'Kredivo PayLater',
            'slug' => 'kredivo_paylater',
            'credit_limit' => 1000000,
            'icon' => '⚡',
            'is_active' => true,
        ]);

        $response = $this->post('/operational/paylater-providers/' . $provider->id . '/limit', [
            'credit_limit' => 4500000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(4500000, $provider->fresh()->credit_limit);
    }

    public function test_user_can_pay_personal_expense_using_paylater_wadah(): void
    {
        $provider = \App\Models\PaylaterProvider::create([
            'name' => 'SPayLater',
            'slug' => 'spaylater',
            'credit_limit' => 5000000,
            'icon' => '🛍️',
            'is_active' => true,
        ]);

        $cashBefore = Account::where('name', 'Cash')->firstOrFail()->current_balance;

        $response = $this->post('/operational/personal-expense', [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => 'paylater_spaylater',
            'amount' => 90000,
            'description' => 'Beli Oli Motor MPX2',
            'tenor_months' => 3,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Uang kas fisik tidak boleh terpotong
        $cashAfter = Account::where('name', 'Cash')->firstOrFail()->current_balance;
        $this->assertEquals($cashBefore, $cashAfter);

        // Hutang dan 3 cicilan terbuat otomatis
        $debt = Debt::where('platform', 'spaylater')->latest()->first();
        $this->assertNotNull($debt);
        $this->assertEquals(90000, $debt->total_obligation);
        $this->assertEquals(3, $debt->tenor_months);
        $this->assertEquals(3, $debt->installments()->count());
        $this->assertEquals(30000, $debt->installments()->first()->amount_due);

        // Limit terpakai terhitung otomatis
        $this->assertEquals(90000, $provider->fresh()->used_credit);
        $this->assertEquals(4910000, $provider->fresh()->available_credit);
    }

    public function test_user_can_delete_unused_paylater_provider(): void
    {
        $provider = \App\Models\PaylaterProvider::create([
            'name' => 'Traveloka PayLater',
            'slug' => 'traveloka_paylater',
            'credit_limit' => 1000000,
            'icon' => '✈️',
            'is_active' => true,
        ]);

        $response = $this->delete('/operational/paylater-providers/' . $provider->id);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('paylater_providers', ['id' => $provider->id]);
    }

    public function test_user_cannot_delete_paylater_provider_with_existing_debts(): void
    {
        $provider = \App\Models\PaylaterProvider::create([
            'name' => 'TikTok PayLater',
            'slug' => 'tiktok_paylater',
            'credit_limit' => 2000000,
            'icon' => '🎵',
            'is_active' => true,
        ]);

        Debt::create([
            'name' => 'Belanja Kaos Distro',
            'platform' => 'tiktok_paylater',
            'borrower_name' => 'Self',
            'principal_amount' => 120000,
            'fee_amount' => 0,
            'total_obligation' => 120000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $response = $this->delete('/operational/paylater-providers/' . $provider->id);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('paylater_providers', ['id' => $provider->id]);
    }

    public function test_user_can_toggle_paylater_provider_active_status(): void
    {
        $provider = \App\Models\PaylaterProvider::create([
            'name' => 'Akulaku PayLater',
            'slug' => 'akulaku_paylater',
            'credit_limit' => 1500000,
            'icon' => '🔴',
            'is_active' => true,
        ]);

        $response = $this->post('/operational/paylater-providers/' . $provider->id . '/toggle-active');

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($provider->fresh()->is_active);

        // Buka kembali
        $response = $this->post('/operational/paylater-providers/' . $provider->id . '/toggle-active');
        $response->assertRedirect();
        $this->assertTrue($provider->fresh()->is_active);
    }
}
