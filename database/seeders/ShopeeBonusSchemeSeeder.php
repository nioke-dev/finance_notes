<?php

namespace Database\Seeders;

use App\Models\ShopeeBonusScheme;
use Illuminate\Database\Seeder;

class ShopeeBonusSchemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'tier_level' => 1,
                'min_orders' => 1,
                'max_orders' => 15,
                'rate_per_order' => 0,
                'is_active' => true,
            ],
            [
                'tier_level' => 2,
                'min_orders' => 16,
                'max_orders' => 20,
                'rate_per_order' => 1000,
                'is_active' => true,
            ],
            [
                'tier_level' => 3,
                'min_orders' => 21,
                'max_orders' => 25,
                'rate_per_order' => 1500,
                'is_active' => true,
            ],
            [
                'tier_level' => 4,
                'min_orders' => 26,
                'max_orders' => null,
                'rate_per_order' => 2000,
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            ShopeeBonusScheme::updateOrCreate(
                ['tier_level' => $tier['tier_level']],
                $tier
            );
        }
    }
}
