<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Expense
            [
                'name' => 'Bensin Motor',
                'type' => 'expense',
                'icon' => 'fuel',
                'color' => '#F59E0B',
                'is_system' => true,
            ],
            [
                'name' => 'Parkir Resto/Pelanggan',
                'type' => 'expense',
                'icon' => 'parking',
                'color' => '#38BDF8',
                'is_system' => true,
            ],
            [
                'name' => 'Makan & Minum di Jalan',
                'type' => 'expense',
                'icon' => 'utensils',
                'color' => '#FB923C',
                'is_system' => true,
            ],
            [
                'name' => 'Perawatan Kendaraan / Servis',
                'type' => 'expense',
                'icon' => 'wrench',
                'color' => '#94A3B8',
                'is_system' => true,
            ],
            [
                'name' => 'Cicilan PayLater',
                'type' => 'expense',
                'icon' => 'credit-card',
                'color' => '#F43F5E',
                'is_system' => true,
            ],
            [
                'name' => 'Sedekah / Bantuan Pribadi',
                'type' => 'expense',
                'icon' => 'heart',
                'color' => '#EC4899',
                'is_system' => true,
            ],
            [
                'name' => 'Biaya Admin Transfer',
                'type' => 'expense',
                'icon' => 'receipt',
                'color' => '#64748B',
                'is_system' => true,
            ],

            // Income
            [
                'name' => 'Pendapatan Hub Shopee',
                'type' => 'income',
                'icon' => 'bike',
                'color' => '#EE4D2D',
                'is_system' => true,
            ],
            [
                'name' => 'Bonus Hub Shopee',
                'type' => 'income',
                'icon' => 'award',
                'color' => '#FBBF24',
                'is_system' => true,
            ],
            [
                'name' => 'Tip Pelanggan QRIS / Cash',
                'type' => 'income',
                'icon' => 'coins',
                'color' => '#10B981',
                'is_system' => true,
            ],
            [
                'name' => 'Pelunasan Utang Teman',
                'type' => 'income',
                'icon' => 'user-check',
                'color' => '#34D399',
                'is_system' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                $cat
            );
        }
    }
}
