<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\Transaction;
use App\Models\WorkDay;
use App\Models\FinancialGoal;
use App\Services\FinancialGoalService;
use App\Services\ReconciliationEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        ReconciliationEngineService $reconciliationService,
        FinancialGoalService $goalService
    ): View
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
                if ($type === 'cod_order' || $type === 'cod_order_completed') {
                    $dayOrdersCount++;
                    $dayRegularIncome += (int) ($meta['bill_amount'] ?? 0);
                    $dayTipIncome += (int) ($meta['tip_amount'] ?? $meta['surplus_amount'] ?? 0);
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

        // Denominasi Kas Fisik & Rekomendasi Beban Dompet (Hasil Closing Terakhir)
        $cashDenomination = null;
        $cashRecommendations = [];
        if ($currentWorkDay) {
            $currentWorkDay->load('cashDenomination');
            $cashDenomination = $currentWorkDay->cashDenomination;
        }

        // Jika shift saat ini belum ada hitungan fisik, ambil dari closing fisik terakhir yang pernah dicatat
        if (!$cashDenomination) {
            $cashDenomination = \App\Models\CashDenomination::with('workDay')->latest()->first();
        }

        if ($cashDenomination) {
            $cashRecommendations = $cashDenomination->getRecommendations();
        }

        $isLateNightHours = (now()->hour >= 0 && now()->hour < 5);
        $yesterdayUnfinalized = $unfinalizedDays->first();

        $activeCodOrders = $currentWorkDay 
            ? $currentWorkDay->activeCodOrders()->onDelivery()->orderBy('accepted_at', 'asc')->get() 
            : collect();

        // 3. Target Belanja Survival & Wishlist Prioritas (Needs vs Wants)
        $financialGoals = FinancialGoal::active()
            ->orderBy('priority_level', 'asc')
            ->orderBy('target_date', 'asc')
            ->get();

        $achievedGoals = FinancialGoal::where('status', 'achieved')
            ->latest('achieved_at')
            ->take(5)
            ->get();

        $safeToSpendData = $goalService->calculateSafeToSpend((int) ($totalDigital + $totalCash));

        // Akun digital wallet utama untuk sinkronisasi saldo malam hari
        $digitalReconAccounts = Account::whereIn('name', [
            'GoPay Merchant',
            'GoPay',
            'Dompet Driver ShopeeFood',
            'ShopeePay',
            'BCA',
            'SeaBank',
        ])->get();

        // 4+1 Elemen Dompet Operasional Driver ShopeeFood
        $driverCashAcc = $accounts->firstWhere('name', 'Cash') ?? $accounts->where('type', AccountType::CASH)->first();
        $driverShopeePayAcc = $accounts->firstWhere('name', 'ShopeePay');
        $driverShopeeFoodAcc = $accounts->firstWhere('name', 'Dompet Driver ShopeeFood');
        $driverGoPayAcc = $accounts->firstWhere('name', 'GoPay');
        $driverGoPayMerchantAcc = $accounts->firstWhere('name', 'GoPay Merchant');

        $driverWalletElements = [
            'cash' => [
                'name' => 'Cash Dompet',
                'account' => $driverCashAcc,
                'balance' => $driverCashAcc?->current_balance ?? 0,
                'icon' => '💵',
                'badge' => 'Uang Fisik Saku',
                'badge_color' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                'desc' => 'Uang tunai kembalian COD & beli bensin cash',
            ],
            'shopeepay' => [
                'name' => 'ShopeePay',
                'account' => $driverShopeePayAcc,
                'balance' => $driverShopeePayAcc?->current_balance ?? 0,
                'icon' => '🟠',
                'badge' => 'E-Wallet Shopee',
                'badge_color' => 'bg-orange-500/20 text-orange-300 border-orange-500/40',
                'desc' => 'Dipotong otomatis sistem saat ambil order COD di resto',
            ],
            'driver_wallet' => [
                'name' => 'Dompet ShopeeFood',
                'account' => $driverShopeeFoodAcc,
                'balance' => $driverShopeeFoodAcc?->current_balance ?? 0,
                'icon' => '🛵',
                'badge' => 'Saldo App Driver',
                'badge_color' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                'desc' => 'Ongkir narik & bonus yang terkumpul hari ini',
            ],
            'gopay' => [
                'name' => 'GoPay Driver',
                'account' => $driverGoPayAcc,
                'balance' => $driverGoPayAcc?->current_balance ?? 0,
                'icon' => '🟢',
                'badge' => 'E-Wallet GoPay',
                'badge_color' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                'desc' => 'Penampung cadangan dana & pembayaran darurat',
            ],
            'gopay_merchant' => [
                'name' => 'GoPay Merchant',
                'account' => $driverGoPayMerchantAcc,
                'balance' => $driverGoPayMerchantAcc?->current_balance ?? 0,
                'icon' => '🏪',
                'badge' => 'QRIS Pelanggan',
                'badge_color' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40',
                'desc' => 'Penerima tip QRIS yang dibayar pelanggan di jalan',
            ],
        ];

        $driverTotalBalance = collect($driverWalletElements)->sum('balance');

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
            'cashRecommendations',
            'isLateNightHours',
            'yesterdayUnfinalized',
            'activeCodOrders',
            'financialGoals',
            'achievedGoals',
            'safeToSpendData',
            'digitalReconAccounts',
            'driverWalletElements',
            'driverTotalBalance'
        ));
    }
}
