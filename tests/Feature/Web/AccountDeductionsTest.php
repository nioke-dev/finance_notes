<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AccountDeduction;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeductionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WorkDay $workDay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);

        $this->user = User::factory()->create([
            'email' => 'driver@keuangan.test',
        ]);
        $this->actingAs($this->user);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => 'active',
            'day_type' => WorkDay::TYPE_DAY_OFF,
        ]);
    }

    public function test_default_accounts_seeder_separates_flazz_and_emoney(): void
    {
        $flazz = Account::where('name', 'Flazz')->first();
        $emoney = Account::where('name', 'e-Money')->first();
        $legacy = Account::where('name', 'Flazz / e-money')->first();
        $deductExpense = Account::where('name', 'Beban Biaya Admin & Pemeliharaan Wadah')->first();

        $this->assertNotNull($flazz, 'Akun Flazz harus ada secara terpisah');
        $this->assertEquals(AccountType::EWALLET, $flazz->type);

        $this->assertNotNull($emoney, 'Akun e-Money harus ada secara terpisah');
        $this->assertEquals(AccountType::EWALLET, $emoney->type);

        $this->assertNull($legacy, 'Akun legacy "Flazz / e-money" tidak boleh ada');

        $this->assertNotNull($deductExpense, 'Akun Beban Biaya Admin & Pemeliharaan Wadah harus ada');
        $this->assertEquals(AccountType::EXPENSE, $deductExpense->type);
    }

    public function test_user_can_deduct_account_balance_instant(): void
    {
        $bca = Account::where('name', 'BCA')->firstOrFail();
        $bca->update(['current_balance' => 500000]);

        $response = $this->post('/operational/account-deduct-balance', [
            'account_id' => $bca->id,
            'amount' => 17000,
            'description' => 'Biaya Admin Debit BCA',
            'transaction_time' => now()->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(483000, $bca->fresh()->current_balance);

        $expense = Account::where('name', 'Beban Biaya Admin & Pemeliharaan Wadah')->firstOrFail();
        $this->assertEquals(17000, $expense->current_balance);

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'description' => '[Biaya Wadah] BCA: Biaya Admin Debit BCA',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Account::class,
            'auditable_id' => $bca->id,
            'action' => 'account_deduction',
        ]);
    }

    public function test_user_can_deduct_balance_and_save_as_recurring_rule(): void
    {
        $spay = Account::where('name', 'ShopeePay')->firstOrFail();
        $spay->update(['current_balance' => 100000]);

        $response = $this->post('/operational/account-deduct-balance', [
            'account_id' => $spay->id,
            'amount' => 2000,
            'description' => 'Biaya Topup Bulanan',
            'transaction_time' => now()->format('Y-m-d\TH:i'),
            'save_as_recurring' => 1,
            'deduction_day' => 28,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(98000, $spay->fresh()->current_balance);

        $deduction = AccountDeduction::where('account_id', $spay->id)->first();
        $this->assertNotNull($deduction);
        $this->assertEquals('Biaya Topup Bulanan', $deduction->name);
        $this->assertEquals(2000, $deduction->amount);
        $this->assertEquals(28, $deduction->deduction_day);
        $this->assertTrue($deduction->isDeductedThisMonth());
    }

    public function test_user_can_store_and_destroy_scheduled_deduction_rule(): void
    {
        $bca = Account::where('name', 'BCA')->firstOrFail();

        $response = $this->post('/operational/account-deduction', [
            'account_id' => $bca->id,
            'name' => 'Biaya Admin Bulanan Debit',
            'amount' => 17000,
            'deduction_day' => 25,
            'notes' => 'Dipotong otomatis akhir bulan',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $deduction = AccountDeduction::where('account_id', $bca->id)->where('name', 'Biaya Admin Bulanan Debit')->first();
        $this->assertNotNull($deduction);
        $this->assertEquals(17000, $deduction->amount);
        $this->assertEquals(25, $deduction->deduction_day);
        $this->assertFalse($deduction->isDeductedThisMonth());

        // Hapus aturan potongan
        $delResponse = $this->delete('/operational/account-deduction/' . $deduction->id);
        $delResponse->assertRedirect();
        $delResponse->assertSessionHas('success');

        $this->assertNull(AccountDeduction::find($deduction->id));
    }

    public function test_user_can_execute_scheduled_deduction_rule(): void
    {
        $bca = Account::where('name', 'BCA')->firstOrFail();
        $bca->update(['current_balance' => 250000]);

        $deduction = AccountDeduction::create([
            'account_id' => $bca->id,
            'name' => 'Admin Kartu Silver',
            'amount' => 15000,
            'deduction_day' => 20,
            'is_active' => true,
        ]);

        $response = $this->post("/operational/account-deduction/{$deduction->id}/execute");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(235000, $bca->fresh()->current_balance);
        $this->assertTrue($deduction->fresh()->isDeductedThisMonth());

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'description' => '[Potongan Rutin Wadah] BCA: Admin Kartu Silver',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Account::class,
            'auditable_id' => $bca->id,
            'action' => 'account_deduction_executed',
        ]);
    }

    public function test_cannot_set_recurring_deduction_on_non_asset_account(): void
    {
        $expenseAccount = Account::where('name', 'Beban Bensin Kendaraan')->firstOrFail();

        $response = $this->post('/operational/account-deduction', [
            'account_id' => $expenseAccount->id,
            'name' => 'Ilegal Deduction',
            'amount' => 10000,
            'deduction_day' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('account_deductions', ['name' => 'Ilegal Deduction']);
    }

    public function test_reset_trial_data_endpoint_works_when_trial_mode_enabled(): void
    {
        config(['app.trial_mode' => true]);

        $response = $this->post('/operational/reset-trial-data');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        // Pastikan akun Flazz dan e-Money tetap tercipta fresh seeder
        $this->assertDatabaseHas('accounts', ['name' => 'Flazz']);
        $this->assertDatabaseHas('accounts', ['name' => 'e-Money']);
    }

    public function test_reset_trial_data_endpoint_aborts_403_when_trial_mode_disabled(): void
    {
        config(['app.trial_mode' => false]);

        $response = $this->post('/operational/reset-trial-data');
        $response->assertStatus(403);
    }

    public function test_personal_expense_with_shopeepay_and_cashback_records_income(): void
    {
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $shopeePay->update(['current_balance' => 100000]);

        $cashbackAccount = Account::where('name', 'Pendapatan Cashback & Reward ShopeePay')->firstOrFail();
        $this->assertEquals(0, $cashbackAccount->current_balance);

        $response = $this->post('/operational/personal-expense', [
            'work_day_id' => $this->workDay->id,
            'description' => 'Beli Paket Data Shopee',
            'amount' => 50000,
            'source_account_id' => $shopeePay->id,
            'cashback_amount' => 3000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // ShopeePay balance: 100.000 - 50.000 + 3.000 = 53.000
        $this->assertEquals(53000, $shopeePay->fresh()->current_balance);
        // Cashback account balance: 3.000
        $this->assertEquals(3000, $cashbackAccount->fresh()->current_balance);

        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'description' => 'Beli Paket Data Shopee',
        ]);

        $this->assertDatabaseHas('transactions', [
            'type' => 'income',
            'description' => '[Cashback ShopeePay] Reward atas Beli Paket Data Shopee',
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'amount' => 50000,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'amount' => 3000,
        ]);
    }

    public function test_fuel_expense_with_shopeepay_and_zero_cashback(): void
    {
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $shopeePay->update(['current_balance' => 100000]);

        $cashbackAccount = Account::where('name', 'Pendapatan Cashback & Reward ShopeePay')->firstOrFail();

        $response = $this->post('/operational/fuel', [
            'work_day_id' => $this->workDay->id,
            'amount' => 20000,
            'source_account_id' => $shopeePay->id,
            'vendor_type' => 'spbu',
            'cashback_amount' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(80000, $shopeePay->fresh()->current_balance);
        $this->assertEquals(0, $cashbackAccount->fresh()->current_balance);

        $this->assertDatabaseMissing('transactions', [
            'type' => 'income',
            'amount' => 0,
        ]);
    }
}

