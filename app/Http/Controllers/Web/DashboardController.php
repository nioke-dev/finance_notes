<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\Transaction;
use App\Models\WorkDay;
use App\Services\ReconciliationEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, ReconciliationEngineService $reconciliationService): View
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

        // Filter tanggal untuk evaluasi harian (default: hari ini)
        $selectedDate = $request->input('date', now()->toDateString());
        $currentWorkDay = WorkDay::whereDate('work_date', $selectedDate)->first()
            ?? WorkDay::latest('work_date')->first();

        // 1. Metrik Operasional Narik Driver
        $dayOrdersCount = 0;
        $dayRegularIncome = 0;
        $dayTipIncome = 0;
        $dayBonusIncome = 0;
        $dayFuelExpense = 0;
        $dayParkingExpense = 0;
        $dayRestoPaidParkingCount = 0;

        // 2. Metrik Kebutuhan Hidup & Finansial Pribadi
        $dayPersonalExpense = 0;
        $dayPersonalIncome = 0;
        $dayDebtRepayment = 0;

        if ($currentWorkDay) {
            $dayTransactions = Transaction::where('work_day_id', $currentWorkDay->id)
                ->with('ledgerEntries.account')
                ->get();

            foreach ($dayTransactions as $tx) {
                $meta = $tx->metadata ?? [];
                $type = $meta['type'] ?? '';

                // Operasional Narik ShopeeFood
                if ($type === 'cod_order') {
                    $dayOrdersCount++;
                    $dayRegularIncome += (int) ($meta['bill_amount'] ?? 0);
                    $dayTipIncome += (int) ($meta['surplus_amount'] ?? 0);
                } elseif ($type === 'quick_fuel') {
                    $dayFuelExpense += $tx->amount;
                } elseif ($type === 'quick_parking') {
                    if (!empty($meta['is_covered_by_resto'])) {
                        $dayRestoPaidParkingCount++;
                    } else {
                        $dayParkingExpense += $tx->amount;
                    }
                } elseif ($type === 'shopeepay_bonus_disbursement') {
                    $dayBonusIncome += (int) ($meta['amount'] ?? 0);
                }

                // Kebutuhan Sehari-hari & Pribadi
                elseif ($type === 'personal_lifestyle_expense' || $type === 'personal_on_beat_expense' || $type === 'spaylater_purchase') {
                    $dayPersonalExpense += $tx->amount;
                } elseif ($type === 'personal_external_income') {
                    $dayPersonalIncome += $tx->amount;
                } elseif ($type === 'debt_repayment') {
                    $dayDebtRepayment += $tx->amount;
                }
            }
        }

        // Perhitungan Total
        $dayTotalIncome = $dayRegularIncome + $dayTipIncome + $dayBonusIncome;
        $dayNetProfit = $dayTotalIncome - ($dayFuelExpense + $dayParkingExpense);

        // Cicilan aktif yang belum lunas untuk modal bayar cicilan cepat
        $activeInstallments = DebtInstallment::with('debt')
            ->where('status', 'unpaid')
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        // Riwayat transaksi terbaru
        $recentTransactions = Transaction::with(['workDay', 'entries.account'])
            ->latest('transaction_time')
            ->take(20)
            ->get();

        // Denominasi Kas Fisik & Rekomendasi Beban Dompet
        $cashDenomination = null;
        $cashRecommendations = [];
        if ($currentWorkDay) {
            $currentWorkDay->load('cashDenomination');
            $cashDenomination = $currentWorkDay->cashDenomination;
            if ($cashDenomination) {
                $cashRecommendations = $cashDenomination->getRecommendations();
            }
        }

        return view('dashboard.index', compact(
            'accounts',
            'totalDigital',
            'totalCash',
            'totalDebt',
            'netWorth',
            'unfinalizedDays',
            'currentWorkDay',
            'selectedDate',
            'dayOrdersCount',
            'dayRegularIncome',
            'dayTipIncome',
            'dayBonusIncome',
            'dayTotalIncome',
            'dayFuelExpense',
            'dayParkingExpense',
            'dayRestoPaidParkingCount',
            'dayNetProfit',
            'dayPersonalExpense',
            'dayPersonalIncome',
            'dayDebtRepayment',
            'activeInstallments',
            'recentTransactions',
            'cashDenomination',
            'cashRecommendations'
        ));
    }
}
