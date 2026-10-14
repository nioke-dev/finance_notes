<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuelAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $fuelTransactions = Transaction::with('ledgerEntries')
            ->where(function ($query) {
                $query->where('description', 'LIKE', '%Bensin%')
                    ->orWhere('description', 'LIKE', '%SPBU%')
                    ->orWhere('description', 'LIKE', '%Madura%')
                    ->orWhere('metadata->type', 'quick_fuel');
            })
            ->orderBy('transaction_time', 'desc')
            ->get();

        $totalSpbuSpent = 0;
        $totalMaduraSpent = 0;
        $totalSpbuLiters = 0.0;
        $totalMaduraBottles = 0;

        foreach ($fuelTransactions as $tx) {
            $amount = (int) $tx->amount;
            $vendor = $tx->metadata['vendor_type'] ?? null;
            $text = strtolower($tx->description ?? '');

            if ($vendor === 'spbu' || str_contains($text, 'spbu')) {
                $totalSpbuSpent += $amount;
                $vol = $tx->metadata['estimated_volume'] ?? round($amount / 10000, 2);
                $totalSpbuLiters += (float) $vol;
            } elseif ($vendor === 'madura' || str_contains($text, 'madura')) {
                $totalMaduraSpent += $amount;
                $bottles = (int) ($amount / 12000);
                $totalMaduraBottles += ($bottles > 0 ? $bottles : 1);
            }
        }

        $totalSpent = $totalSpbuSpent + $totalMaduraSpent;
        $totalLiters = $totalSpbuLiters + (float) $totalMaduraBottles; // botol eceran setara 1 Liter

        // Hitung persentase ketergantungan eceran Madura
        $maduraRatio = $totalSpent > 0 ? round(($totalMaduraSpent / $totalSpent) * 100, 1) : 0;
        $isMaduraHigh = $maduraRatio > 40;

        return view('pages.analytics.fuel', compact(
            'fuelTransactions',
            'totalSpbuSpent',
            'totalMaduraSpent',
            'totalSpbuLiters',
            'totalMaduraBottles',
            'totalSpent',
            'totalLiters',
            'maduraRatio',
            'isMaduraHigh'
        ));
    }
}
