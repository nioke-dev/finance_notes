<?php

namespace Tests\Feature\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi seeder membuat seluruh akun esensial driver.
     */
    public function test_seeder_creates_all_essential_driver_accounts(): void
    {
        $this->seed(DefaultAccountsSeeder::class);

        // Verifikasi akun utama
        $essentialAccounts = [
            'BCA' => AccountType::BANK,
            'SeaBank' => AccountType::BANK,
            'ShopeePay' => AccountType::EWALLET,
            'GoPay' => AccountType::EWALLET,
            'GoPay Merchant' => AccountType::EWALLET,
            'Cash' => AccountType::CASH,
            'Pendapatan Hub ShopeeFood' => AccountType::INCOME,
            'Bonus Hub ShopeeFood' => AccountType::INCOME,
            'Beban Bensin Kendaraan' => AccountType::EXPENSE,
            'Beban Parkir' => AccountType::EXPENSE,
            'Beban Serba-serbi & Insidental Lapangan' => AccountType::EXPENSE,
        ];

        foreach ($essentialAccounts as $name => $expectedType) {
            $account = Account::where('name', $name)->first();
            $this->assertNotNull($account, "Akun '{$name}' tidak ditemukan setelah seeder dijalankan.");
            $this->assertEquals($expectedType, $account->type);
            $this->assertEquals(0, $account->current_balance);
            $this->assertTrue($account->is_active);
        }

        $this->assertEquals(20, Account::count());
    }

    /**
     * Memverifikasi seeder bersifat idempoten (bisa dijalankan berkali-kali tanpa duplikasi).
     */
    public function test_seeder_can_run_multiple_times_without_duplicate_errors(): void
    {
        $this->seed(DefaultAccountsSeeder::class);
        $this->assertEquals(20, Account::count());

        // Jalankan kedua kali
        $this->seed(DefaultAccountsSeeder::class);
        $this->assertEquals(20, Account::count());
    }
}
