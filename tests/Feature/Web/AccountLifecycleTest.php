<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase
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

    public function test_user_can_create_new_bank_account(): void
    {
        $response = $this->post('/operational/account', [
            'name' => 'Bank Mandiri',
            'type' => 'bank',
            'initial_balance' => 250000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $account = Account::where('name', 'Bank Mandiri')->first();
        $this->assertNotNull($account);
        $this->assertEquals(AccountType::BANK, $account->type);
        $this->assertEquals(250000, $account->current_balance);
        $this->assertEquals(250000, $account->initial_balance);
        $this->assertTrue($account->is_active);
    }

    public function test_user_can_create_new_ewallet_account(): void
    {
        $response = $this->post('/operational/account', [
            'name' => 'DANA Pribadi',
            'type' => 'ewallet',
            'initial_balance' => 50000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $account = Account::where('name', 'DANA Pribadi')->first();
        $this->assertNotNull($account);
        $this->assertEquals(AccountType::EWALLET, $account->type);
        $this->assertEquals(50000, $account->current_balance);
        $this->assertTrue($account->is_active);
    }

    public function test_account_cannot_be_deactivated_when_it_still_has_balance(): void
    {
        $bca = Account::where('name', 'BCA')->firstOrFail();
        $bca->update(['current_balance' => 150000]);

        $response = $this->post('/operational/account/' . $bca->id . '/toggle-active');

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertTrue($bca->fresh()->is_active);
    }

    public function test_account_can_be_deactivated_when_balance_is_zero(): void
    {
        $bca = Account::where('name', 'BCA')->firstOrFail();
        $bca->update(['current_balance' => 0]);

        $response = $this->post('/operational/account/' . $bca->id . '/toggle-active');

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($bca->fresh()->is_active);

        // Can be reactivated
        $reactivateResponse = $this->post('/operational/account/' . $bca->id . '/toggle-active');
        $reactivateResponse->assertRedirect();
        $reactivateResponse->assertSessionHas('success');
        $this->assertTrue($bca->fresh()->is_active);
    }

    public function test_account_with_ledger_history_cannot_be_deleted(): void
    {
        $bca = Account::where('name', 'BCA')->firstOrFail();
        $bca->update(['current_balance' => 0]);

        $tx = Transaction::create([
            'work_day_id' => $this->workDay->id,
            'type' => 'income',
            'amount' => 50000,
            'description' => 'Test Transaction',
            'transaction_time' => now(),
        ]);

        LedgerEntry::create([
            'transaction_id' => $tx->id,
            'account_id' => $bca->id,
            'entry_type' => \App\Enums\EntryType::DEBIT,
            'amount' => 50000,
            'created_at' => now(),
        ]);

        $response = $this->delete('/operational/account/' . $bca->id);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull(Account::find($bca->id));
    }

    public function test_unused_account_with_zero_ledger_can_be_deleted(): void
    {
        // GoPay Merchant bawaan seeder memiliki saldo 0 dan belum ada transaksi
        $gopayMerchant = Account::where('name', 'GoPay Merchant')->firstOrFail();
        $this->assertEquals(0, $gopayMerchant->current_balance);
        $this->assertFalse($gopayMerchant->ledgerEntries()->exists());

        $response = $this->delete('/operational/account/' . $gopayMerchant->id);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNull(Account::find($gopayMerchant->id));
    }
}
