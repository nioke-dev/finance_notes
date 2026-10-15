<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Driver User for Web & Mobile
        User::updateOrCreate(
            ['email' => 'driver@keuangan.test'],
            [
                'name' => 'Driver ShopeeFood Malang',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            ]
        );

        $this->call([
            DefaultAccountsSeeder::class,
            CategorySeeder::class,
            ShopeeBonusSchemeSeeder::class,
        ]);
    }
}
