<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Seeder;

class DefaultAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Bank & E-Wallet
            ['name' => 'BCA', 'type' => AccountType::BANK],
            ['name' => 'SeaBank', 'type' => AccountType::BANK],
            ['name' => 'ShopeePay', 'type' => AccountType::EWALLET],
            ['name' => 'GoPay', 'type' => AccountType::EWALLET],
            ['name' => 'GoPay Merchant', 'type' => AccountType::EWALLET],
            ['name' => 'Dompet Driver ShopeeFood', 'type' => AccountType::EWALLET],
            ['name' => 'Cash', 'type' => AccountType::CASH],
            ['name' => 'Flazz / e-money', 'type' => AccountType::EWALLET],
            ['name' => 'Talangan Pesanan COD', 'type' => AccountType::RECEIVABLE],

            // Income
            ['name' => 'Pendapatan Hub ShopeeFood', 'type' => AccountType::INCOME],
            ['name' => 'Bonus Hub ShopeeFood', 'type' => AccountType::INCOME],
            ['name' => 'Tip Pelanggan ShopeeFood', 'type' => AccountType::INCOME],
            ['name' => 'Tip Cash / Surplus COD', 'type' => AccountType::INCOME],
            ['name' => 'Pendapatan QRIS / Tip Non-Tunai', 'type' => AccountType::INCOME],

            // Expense
            ['name' => 'Beban Bensin Kendaraan', 'type' => AccountType::EXPENSE],
            ['name' => 'Beban Parkir', 'type' => AccountType::EXPENSE],
            ['name' => 'Beban Makanan & Minuman Driver', 'type' => AccountType::EXPENSE],
            ['name' => 'Beban Serba-serbi & Insidental Lapangan', 'type' => AccountType::EXPENSE],
            ['name' => 'Beban Biaya Admin Transfer', 'type' => AccountType::EXPENSE],
            ['name' => 'Beban Sedekah & Bantuan Pribadi', 'type' => AccountType::EXPENSE],
            ['name' => 'Beban Selisih Kas (Hilang/Kurang)', 'type' => AccountType::EXPENSE],
            ['name' => 'Pendapatan Selisih Kas (Surplus Tak Terlacak)', 'type' => AccountType::INCOME],
        ];

        foreach ($accounts as $acc) {
            Account::updateOrCreate(
                ['name' => $acc['name']],
                [
                    'type' => $acc['type'],
                    'current_balance' => 0,
                    'initial_balance' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
