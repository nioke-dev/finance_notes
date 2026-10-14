<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WorkDay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DriverAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->input('days', 7);
        $startDate = Carbon::today()->subDays($days - 1)->toDateString();

        $workDays = WorkDay::with('latestSnapshot')
            ->whereDate('work_date', '>=', $startDate)
            ->orderBy('work_date', 'asc')
            ->get();

        $totalTrips = $workDays->sum(function (WorkDay $w) {
            return (int) ($w->total_orders ?? $w->latestSnapshot?->completed_orders ?? 0);
        });

        $totalShopeeIncome = $workDays->sum(function (WorkDay $w) {
            $hub = (int) ($w->latestSnapshot?->hub_income ?? 0);
            $bonus = (int) ($w->latestSnapshot?->hub_bonus ?? 0);
            $tip = (int) ($w->latestSnapshot?->customer_tips ?? 0);
            return $hub + $bonus + $tip;
        });

        $bonusDaysCount = $workDays->filter(function (WorkDay $w) {
            return ((int) ($w->latestSnapshot?->hub_bonus ?? 0)) > 0;
        })->count();

        $totalOnlineHours = $workDays->sum('online_hours') ?: 1;
        $hourlyRate = (int) ($totalShopeeIncome / ($totalOnlineHours ?: 1));

        return view('pages.analytics.driver', compact(
            'workDays',
            'totalTrips',
            'totalShopeeIncome',
            'bonusDaysCount',
            'totalOnlineHours',
            'hourlyRate',
            'days'
        ));
    }
}
