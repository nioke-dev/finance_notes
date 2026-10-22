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
use App\Models\Subscription;
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
        $accounts = Account::active()
            ->whereIn('type', [AccountType::CASH, AccountType::BANK, AccountType::EWALLET])
            ->with(['activeDeductions'])
            ->get();

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
        $hasCustomDate = $request->has('date');
        $selectedDate = $request->input('date', now()->toDateString());

        // Filter tanggal untuk evaluasi harian (default: hari ini)
        $hasCustomDate = $request->has('date');
        $selectedDate = $request->input('date', now()->toDateString());

        if ($hasCustomDate) {
            // Jika user memilih tanggal secara eksplisit di filter kalender (misal tanggal 20), buat atau muat WorkDay hari tersebut
            $currentWorkDay = WorkDay::firstOrCreate(
                ['work_date' => $selectedDate],
                [
                    'day_type' => WorkDay::TYPE_ON_BID,
                    'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
                    'opened_at' => Carbon::parse($selectedDate)->setTime(8, 0, 0),
                ]
            );
        } else {
            $currentWorkDay = WorkDay::whereDate('work_date', $selectedDate)->first();
        }

        // 1. Metrik Operasional Narik Driver
        $dayOrdersCount = 0;
        $dayRegularIncome = 0;
        $dayCodBillSettled = 0;
        $dayTipIncome = 0;
        $dayBonusIncome = 0;
        $dayFuelExpense = 0;
        $dayParkingExpense = 0;
        $dayRestoPaidParkingCount = 0;
        $dayPaidParkingCount = 0;

        // 2. Metrik Kebutuhan Hidup & Finansial Pribadi
        $dayPersonalExpense = 0;
        $dayPersonalIncome = 0;
        $dayDebtRepayment = 0;

        // Rincian Elemen Tip Presisi
        $dayCodQrisTip = 0;
        $dayCodCashTip = 0;
        $dayDirectCashTip = 0;
        $dayDirectDigitalTip = 0;
        $dayReconSurplusTip = 0;

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
                    $dayCodBillSettled += (int) ($meta['bill_amount'] ?? 0);
                    $tip = (int) ($meta['tip_amount'] ?? $meta['surplus_amount'] ?? 0);
                    $method = strtolower($meta['payment_method'] ?? 'cash');
                    if ($method === 'qris') {
                        $dayCodQrisTip += $tip;
                    } else {
                        $dayCodCashTip += $tip;
                    }
                    $dayTipIncome += $tip;
                } elseif ($type === 'direct_customer_tip') {
                    $tip = (int) ($meta['tip_amount'] ?? $tx->amount);
                    $method = strtolower($meta['payment_method'] ?? 'cash');
                    if ($method === 'cash') {
                        $dayDirectCashTip += $tip;
                    } else {
                        $dayDirectDigitalTip += $tip;
                    }
                    $dayTipIncome += $tip;
                } elseif ($type === 'quick_fuel') {
                    $dayFuelExpense += $tx->amount;
                } elseif ($type === 'quick_parking') {
                    if (!empty($meta['is_covered_by_resto'])) {
                        $dayRestoPaidParkingCount++;
                    } else {
                        $dayParkingExpense += $tx->amount;
                        $dayPaidParkingCount++;
                    }
                } elseif ($type === 'shopeepay_bonus_disbursement' || $type === 'hub_bonus_payout') {
                    $dayBonusIncome += (int) ($meta['amount'] ?? $tx->amount);
                } elseif ($type === 'digital_reconciliation_surplus') {
                    $accId = (int) ($meta['account_id'] ?? 0);
                    $acc = $accounts->firstWhere('id', $accId);
                    $diffAmount = (int) ($meta['diff'] ?? $tx->amount);
                    if ($acc && $acc->name === 'ShopeePay') {
                        $dayRegularIncome += $diffAmount;
                    } else {
                        $dayReconSurplusTip += $diffAmount;
                        $dayTipIncome += $diffAmount;
                    }
                }

                // Kebutuhan Sehari-hari & Pribadi
                elseif ($type === 'personal_lifestyle_expense' || $type === 'personal_on_beat_expense' || $type === 'spaylater_purchase' || $type === 'field_incidental_expense') {
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
        $recentTransactions = Transaction::with(['workDay', 'ledgerEntries.account'])
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

        // Jika shift saat ini belum ada hitungan fisik, ambil dari closing fisik terakhir atau saldo modal awal
        if (!$cashDenomination || $cashDenomination->calculateTotal() === 0) {
            $cashDenomination = \App\Models\CashDenomination::with('workDay')->where('total_physical_amount', '>', 0)->latest('id')->first();
            if (!$cashDenomination) {
                $cashAcc = $accounts->firstWhere('type', AccountType::CASH);
                if ($cashAcc && !empty($cashAcc->metadata['denominations'])) {
                    $cashDenomination = new \App\Models\CashDenomination($cashAcc->metadata['denominations']);
                    $cashDenomination->total_physical_amount = $cashDenomination->calculateTotal();
                } elseif ($cashAcc && ($cashAcc->current_balance > 0 || $cashAcc->initial_balance > 0)) {
                    $bal = $cashAcc->current_balance > 0 ? $cashAcc->current_balance : $cashAcc->initial_balance;
                    $cashDenomination = new \App\Models\CashDenomination(\App\Models\CashDenomination::decomposeAmount($bal));
                    $cashDenomination->total_physical_amount = $bal;
                }
            }
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

        // 4. Misi Bebas Hutang 31 Oktober & Cicilan Jatuh Tempo Bulan Ini
        $monthEnd = now()->endOfMonth();
        $activeDebts = Debt::active()->with(['installments' => function($q) {
            $q->orderBy('due_date', 'asc');
        }])->get();
        $totalDebtObligation = (int) $activeDebts->sum('total_obligation');
        $totalDebtRemaining = (int) $activeDebts->sum('remaining_amount');

        $thisMonthInstallments = DebtInstallment::with('debt')
            ->where('status', 'unpaid')
            ->where('due_date', '<=', $monthEnd)
            ->orderBy('due_date', 'asc')
            ->get();
        $thisMonthDebtDue = (int) $thisMonthInstallments->sum('amount_due');
        $daysRemainingInMonth = max(1, (int) round(now()->startOfDay()->diffInDays($monthEnd->copy()->startOfDay(), false) + 1));
        $dailyDebtQuota = (int) ceil($thisMonthDebtDue / $daysRemainingInMonth);

        $debtFreedomData = [
            'target_date' => $monthEnd,
            'target_label' => $monthEnd->translatedFormat('d F Y'),
            'days_remaining' => $daysRemainingInMonth,
            'total_debt_remaining' => $totalDebtRemaining,
            'this_month_due' => $thisMonthDebtDue,
            'daily_quota' => $dailyDebtQuota,
            'installments' => $thisMonthInstallments,
        ];

        // 5. Langganan & Tagihan Rutin Bulanan (Subscriptions)
        $subscriptions = Subscription::orderBy('due_day_of_month', 'asc')->get();
        $unpaidSubscriptions = $subscriptions->filter(fn($s) => $s->is_active && !$s->isPaidThisMonth());
        $totalMonthlySubscriptions = (int) $subscriptions->where('is_active', true)->sum('amount');
        $unpaidSubscriptionsAmount = (int) $unpaidSubscriptions->sum('amount');

        $safeToSpendData = $goalService->calculateSafeToSpend((int) ($totalDigital + $totalCash));

        // 3 Akun wadah digital wajib untuk checkpoint di jalan (ShopeePay, GoPay, GoPay Merchant)
        $digitalReconAccounts = Account::whereIn('name', ['ShopeePay', 'GoPay', 'GoPay Merchant'])
            ->where('is_active', true)
            ->orderByRaw("CASE 
                WHEN name = 'ShopeePay' THEN 1 
                WHEN name = 'GoPay' THEN 2 
                WHEN name = 'GoPay Merchant' THEN 3 
                ELSE 4 END")
            ->get();

        // 4 Elemen Saldo Likuid Uang Riil Driver
        $driverCashAcc = $accounts->firstWhere('name', 'Cash') ?? $accounts->where('type', AccountType::CASH)->first();
        $driverShopeePayAcc = $accounts->firstWhere('name', 'ShopeePay');
        $driverGoPayAcc = $accounts->firstWhere('name', 'GoPay');
        $driverGoPayMerchantAcc = $accounts->firstWhere('name', 'GoPay Merchant');

        $driverWalletElements = [
            'cash' => [
                'name' => 'Cash Dompet',
                'account' => $driverCashAcc,
                'balance' => $driverCashAcc?->current_balance ?? 0,
                'icon' => '💵',
                'badge' => 'Fisik Saku',
                'badge_color' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                'desc' => 'Uang tunai kembalian COD & beli bensin cash',
            ],
            'shopeepay' => [
                'name' => 'ShopeePay',
                'account' => $driverShopeePayAcc,
                'balance' => $driverShopeePayAcc?->current_balance ?? 0,
                'icon' => '🟠',
                'badge' => 'E-Wallet Utama',
                'badge_color' => 'bg-orange-500/20 text-orange-300 border-orange-500/40',
                'desc' => 'Gaji trip ShopeeFood otomatis masuk ke sini & talangan COD resto',
            ],
            'gopay' => [
                'name' => 'GoPay',
                'account' => $driverGoPayAcc,
                'balance' => $driverGoPayAcc?->current_balance ?? 0,
                'icon' => '🟢',
                'badge' => 'E-Wallet Reguler',
                'badge_color' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                'desc' => 'Aplikasi GoPay umum/konsumen (bayar hutang nongkrong/kafe, transfer, belanja)',
            ],
            'gopay_merchant' => [
                'name' => 'GoPay Merchant',
                'account' => $driverGoPayMerchantAcc,
                'balance' => $driverGoPayMerchantAcc?->current_balance ?? 0,
                'icon' => '🏪',
                'badge' => 'Perantara QRIS',
                'badge_color' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40',
                'desc' => 'Perantara terima tip QRIS customer / bayar hutang titipan teman kafe',
            ],
        ];

        $driverTotalBalance = collect($driverWalletElements)->sum('balance');

        // Rekapitulasi Riwayat Performa Narik ShopeeFood Hari Ini (Data Read-Only dari App Driver)
        $shopeeReportData = [
            'total_orders' => $dayOrdersCount,
            'regular_income' => $dayRegularIncome,
            'bonus_income' => $dayBonusIncome,
            'tip_income' => $dayTipIncome,
            'total_income' => $dayTotalIncome,
        ];

        // Voice Memos (Kotak Hitam Helm) Hari Ini & Terkini Lintas Tanggal
        $todayVoiceMemos = $currentWorkDay
            ? $currentWorkDay->voiceMemos()->with('workDay')->latest('recorded_at')->latest('id')->get()
            : collect();

        $recentVoiceMemos = \App\Models\VoiceMemo::with('workDay')
            ->latest('recorded_at')
            ->latest('id')
            ->take(15)
            ->get();

        // Wadah PayLater Aktif untuk Belanja Kebutuhan
        $paylaterProviders = \App\Models\PaylaterProvider::active()->get();

        // Riwayat Checkpoint Snapshot Narik Hari Ini
        $driverSnapshots = $currentWorkDay
            ? $currentWorkDay->driverSnapshots()->latest('recorded_at')->get()
            : collect();
        $latestSnapshot = $currentWorkDay?->latestSnapshot;

        // Total tagihan makanan COD (aktif atau selesai via QRIS) untuk panduan pengurang modal rekonsiliasi
        $todayCodQrisBillTotal = $currentWorkDay 
            ? (int) $currentWorkDay->activeCodOrders()
                ->where(function ($q) {
                    $q->where('payment_method', 'qris')
                      ->orWhere('status', 'on_delivery');
                })
                ->sum('bill_amount')
            : 0;

        // 5. Metrik Finansial Harian (Mode Driver vs Mode Overall) & Komparasi Parkir vs Tip Presisi
        $shopeeAppTip = (int) ($latestSnapshot?->customer_tips ?? 0);
        $totalDriverTip = $shopeeAppTip + $dayCodQrisTip + $dayCodCashTip + $dayDirectCashTip + $dayDirectDigitalTip + $dayReconSurplusTip;
        if ($totalDriverTip === 0 && $dayTipIncome > 0) {
            $totalDriverTip = $dayTipIncome;
        }

        $driverDeliveryFee = (int) ($latestSnapshot?->delivery_fee ?? 0);
        $driverHubIncome = (int) ($latestSnapshot?->hub_income ?? $dayRegularIncome);
        $driverHubBonus = (int) ($latestSnapshot?->hub_bonus ?? $dayBonusIncome);
        $driverShopeeNetIncome = $driverDeliveryFee + $driverHubIncome + $driverHubBonus;

        if ($latestSnapshot === null && $driverShopeeNetIncome === 0) {
            $driverShopeeNetIncome = $dayRegularIncome + $dayBonusIncome;
        }

        $driverTotalIncome = $driverShopeeNetIncome + $totalDriverTip;
        $driverTotalOutcome = $dayFuelExpense + $dayParkingExpense;
        $driverNetProfit = $driverTotalIncome - $driverTotalOutcome;

        $parkingNet = $totalDriverTip - $dayParkingExpense;
        $isParkingCovered = ($parkingNet >= 0);

        // Mode Overall (Finansial Keseluruhan)
        $overallTotalIncome = $driverTotalIncome + $dayPersonalIncome;
        $overallTotalOutcome = $driverTotalOutcome + $dayPersonalExpense + $dayDebtRepayment;
        $overallNetCashflow = $overallTotalIncome - $overallTotalOutcome;

        $dailySummaryData = [
            'mode_driver' => [
                'income' => $driverTotalIncome,
                'outcome' => $driverTotalOutcome,
                'net' => $driverNetProfit,
                'breakdown_income' => [
                    'delivery_fee' => $driverDeliveryFee,
                    'hub_income' => $driverHubIncome,
                    'hub_bonus' => $driverHubBonus,
                    'shopee_total' => $driverShopeeNetIncome,
                    'total_tips' => $totalDriverTip,
                ],
                'breakdown_outcome' => [
                    'fuel' => $dayFuelExpense,
                    'parking' => $dayParkingExpense,
                ],
                'tip_details' => [
                    'shopee_app_tip' => $shopeeAppTip,
                    'cod_qris_tip' => $dayCodQrisTip,
                    'cod_cash_tip' => $dayCodCashTip,
                    'direct_cash_tip' => $dayDirectCashTip,
                    'direct_digital_tip' => $dayDirectDigitalTip,
                    'recon_surplus_tip' => $dayReconSurplusTip,
                    'total' => $totalDriverTip,
                ],
                'parking_eval' => [
                    'expense' => $dayParkingExpense,
                    'total_tips' => $totalDriverTip,
                    'net' => $parkingNet,
                    'is_covered' => $isParkingCovered,
                ],
            ],
            'mode_overall' => [
                'income' => $overallTotalIncome,
                'outcome' => $overallTotalOutcome,
                'net' => $overallNetCashflow,
                'breakdown_income' => [
                    'driver_income' => $driverTotalIncome,
                    'personal_income' => $dayPersonalIncome,
                ],
                'breakdown_outcome' => [
                    'driver_operational' => $driverTotalOutcome,
                    'personal_expense' => $dayPersonalExpense,
                    'debt_repayment' => $dayDebtRepayment,
                ],
            ],
        ];

        // Akun-akun penerima uang tip langsung di lapangan
        $tipAccounts = Account::whereIn('name', ['Cash', 'GoPay Merchant', 'ShopeePay', 'GoPay', 'BCA', 'SeaBank'])
            ->where('is_active', true)
            ->orderByRaw("CASE 
                WHEN name = 'Cash' THEN 1 
                WHEN name = 'GoPay Merchant' THEN 2 
                WHEN name = 'ShopeePay' THEN 3 
                WHEN name = 'GoPay' THEN 4 
                WHEN name = 'BCA' THEN 5 
                ELSE 6 END")
            ->get();

        // 6. Kelayakan & Status Pencairan Bonus Hub ShopeeFood (Evaluasi Jam 20:00+ & Otomasi Jam 23:00)
        $nowWib = now()->timezone('Asia/Jakarta');
        $currentHour = (int) $nowWib->format('H');
        $isAfter20 = ($currentHour >= 20 || $currentHour < 5);
        $isAfter23 = ($currentHour >= 23 || $currentHour < 5);

        $existingBonusTx = $currentWorkDay 
            ? Transaction::where('work_day_id', $currentWorkDay->id)
                ->where(function ($q) {
                    $q->whereJsonContains('metadata->type', 'hub_bonus_payout')
                      ->orWhereJsonContains('metadata->type', 'shopeepay_bonus_disbursement');
                })->first()
            : null;

        $isBonusClaimed = ($existingBonusTx !== null);
        $claimedBonusAmount = $existingBonusTx ? (int) ($existingBonusTx->metadata['amount'] ?? $existingBonusTx->amount) : 0;

        $pendingBonusAmount = (int) ($latestSnapshot?->hub_bonus ?? 0);
        if ($pendingBonusAmount === 0 && ($currentWorkDay?->is_performance_eligible ?? false) && ($currentWorkDay?->total_orders ?? 0) > 15) {
            $pendingBonusAmount = app(\App\Services\ShopeeBonusCalculatorService::class)->calculateBonus((int) $currentWorkDay->total_orders);
        }

        $hubBonusEligibility = [
            'current_hour' => $currentHour,
            'is_after_20' => $isAfter20,
            'is_after_23' => $isAfter23,
            'is_online_9_hours' => (bool) ($currentWorkDay?->is_performance_eligible ?? false),
            'total_orders' => (int) ($currentWorkDay?->total_orders ?? $latestSnapshot?->completed_orders ?? $dayOrdersCount ?? 0),
            'pending_bonus_amount' => $pendingBonusAmount,
            'is_bonus_claimed' => $isBonusClaimed,
            'claimed_bonus_amount' => $claimedBonusAmount,
            'should_show_23_alert' => ($isAfter23 && !$isBonusClaimed),
        ];

        return view('dashboard.index', compact(
            'accounts',
            'paylaterProviders',
            'todayVoiceMemos',
            'recentVoiceMemos',
            'totalDigital',
            'totalCash',
            'totalDebt',
            'netWorth',
            'unfinalizedDays',
            'currentWorkDay',
            'selectedDate',
            'dayOrdersCount',
            'dayRegularIncome',
            'dayCodBillSettled',
            'dayTipIncome',
            'dayBonusIncome',
            'dayTotalIncome',
            'dayFuelExpense',
            'dayParkingExpense',
            'dayRestoPaidParkingCount',
            'dayPaidParkingCount',
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
            'driverTotalBalance',
            'shopeeReportData',
            'debtFreedomData',
            'subscriptions',
            'unpaidSubscriptions',
            'totalMonthlySubscriptions',
            'unpaidSubscriptionsAmount',
            'driverSnapshots',
            'latestSnapshot',
            'todayCodQrisBillTotal',
            'driverCashAcc',
            'dailySummaryData',
            'tipAccounts',
            'hubBonusEligibility'
        ));
    }
}
