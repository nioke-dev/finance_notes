<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use App\Services\ReconciliationEngineService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(ReconciliationEngineService $reconciliationService): View
    {
        $accounts = Account::active()->get();

        $totalDigital = $accounts->filter(fn (Account $a) => in_array($a->type, [AccountType::BANK, AccountType::EWALLET]))
            ->sum('current_balance');

        $totalCash = $accounts->filter(fn (Account $a) => $a->type === AccountType::CASH)
            ->sum('current_balance');

        $totalDebt = Debt::active()->sum('total_obligation');
        $netWorth = ($totalDigital + $totalCash) - $totalDebt;

        $unfinalizedDays = WorkDay::unfinalized()
            ->whereDate('work_date', '<', now()->toDateString())
            ->orderBy('work_date', 'asc')
            ->get();

        return view('dashboard.index', compact(
            'accounts',
            'totalDigital',
            'totalCash',
            'totalDebt',
            'netWorth',
            'unfinalizedDays'
        ));
    }
}
