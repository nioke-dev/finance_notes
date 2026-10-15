<?php

namespace Database\Seeders;

use App\Models\PaylaterProvider;
use Illuminate\Database\Seeder;

class PaylaterProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Shopee PayLater (SPayLater)',
                'slug' => 'spaylater',
                'icon' => '🛍️',
                'credit_limit' => 0,
            ],
            [
                'name' => 'GoPay Later (GoPay Cicil)',
                'slug' => 'gopay_later',
                'icon' => '🟢',
                'credit_limit' => 0,
            ],
            [
                'name' => 'TikTok PayLater',
                'slug' => 'tiktok_paylater',
                'icon' => '🎵',
                'credit_limit' => 0,
            ],
            [
                'name' => 'Kredivo PayLater',
                'slug' => 'kredivo_paylater',
                'icon' => '⚡',
                'credit_limit' => 0,
            ],
            [
                'name' => 'Akulaku PayLater',
                'slug' => 'akulaku_paylater',
                'icon' => '🔴',
                'credit_limit' => 0,
            ],
            [
                'name' => 'Indodana PayLater',
                'slug' => 'indodana_paylater',
                'icon' => '🟡',
                'credit_limit' => 0,
            ],
        ];

        foreach ($providers as $item) {
            PaylaterProvider::firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'icon' => $item['icon'],
                    'credit_limit' => $item['credit_limit'],
                    'is_active' => true,
                ]
            );
        }
    }
}
