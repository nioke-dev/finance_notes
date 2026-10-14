<?php

namespace Tests\Unit\Models;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi auto-casting type ke Enum AccountType.
     */
    public function test_account_casts_type_to_enum_instance(): void
    {
        $account = Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'current_balance' => 500000,
            'initial_balance' => 500000,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(AccountType::class, $account->type);
        $this->assertEquals(AccountType::BANK, $account->type);
        $this->assertEquals('bank', $account->type->value);
    }

    /**
     * Memverifikasi saldo ter-cast sebagai integer murni.
     */
    public function test_account_balances_are_strictly_cast_to_integer(): void
    {
        $account = Account::create([
            'name' => 'Cash',
            'type' => 'cash',
            'current_balance' => 125000,
            'initial_balance' => 100000,
            'is_active' => true,
        ]);

        $this->assertIsInt($account->current_balance);
        $this->assertIsInt($account->initial_balance);
        $this->assertIsBool($account->is_active);
    }

    /**
     * Memverifikasi seluruh scopes (active, digital, physical, assets).
     */
    public function test_scopes_filter_accounts_correctly(): void
    {
        Account::create(['name' => 'BCA', 'type' => AccountType::BANK, 'is_active' => true]);
        Account::create(['name' => 'ShopeePay', 'type' => AccountType::EWALLET, 'is_active' => true]);
        Account::create(['name' => 'Cash', 'type' => AccountType::CASH, 'is_active' => true]);
        Account::create(['name' => 'Utang Teman', 'type' => AccountType::RECEIVABLE, 'is_active' => true]);
        Account::create(['name' => 'Beban Parkir', 'type' => AccountType::EXPENSE, 'is_active' => true]);
        Account::create(['name' => 'Akun Pasif', 'type' => AccountType::BANK, 'is_active' => false]);

        $this->assertEquals(5, Account::active()->count());
        $this->assertEquals(3, Account::digital()->count()); // BCA, ShopeePay, Akun Pasif
        $this->assertEquals(2, Account::digital()->active()->count()); // BCA, ShopeePay
        $this->assertEquals(1, Account::physical()->count()); // Cash
        $this->assertEquals(4, Account::assets()->active()->count()); // BCA, ShopeePay, Cash, Utang Teman
    }
}
