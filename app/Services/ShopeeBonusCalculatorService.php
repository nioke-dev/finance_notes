<?php

namespace App\Services;

use App\Models\ShopeeBonusScheme;

class ShopeeBonusCalculatorService
{
    public function calculateBonus(int $totalEligibleOrders): int
    {
        if ($totalEligibleOrders <= 15) {
            return 0;
        }

        $schemes = ShopeeBonusScheme::active()->orderBy('tier_level')->get();

        // Fallback default jika tabel belum terisi data seeder
        if ($schemes->isEmpty()) {
            return $this->calculateWithDefaultRules($totalEligibleOrders);
        }

        $totalBonus = 0;

        foreach ($schemes as $scheme) {
            if ($totalEligibleOrders < $scheme->min_orders) {
                continue;
            }

            $maxEligibleInThisTier = $scheme->max_orders 
                ? min($totalEligibleOrders, $scheme->max_orders) 
                : $totalEligibleOrders;

            $ordersInThisTier = ($maxEligibleInThisTier - $scheme->min_orders) + 1;

            if ($ordersInThisTier > 0) {
                $totalBonus += $ordersInThisTier * $scheme->rate_per_order;
            }
        }

        return $totalBonus;
    }

    protected function calculateWithDefaultRules(int $orders): int
    {
        $bonus = 0;

        // Tier 2 (16 - 20): Rp1.000
        if ($orders >= 16) {
            $count = min($orders, 20) - 15;
            $bonus += $count * 1000;
        }

        // Tier 3 (21 - 25): Rp1.500
        if ($orders >= 21) {
            $count = min($orders, 25) - 20;
            $bonus += $count * 1500;
        }

        // Tier 4 (26+): Rp2.000
        if ($orders >= 26) {
            $count = $orders - 25;
            $bonus += $count * 2000;
        }

        return $bonus;
    }
}
