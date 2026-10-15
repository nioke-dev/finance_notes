<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use Illuminate\Http\Request;

use App\Models\PaylaterProvider;

class WalletWebController extends Controller
{
    /**
     * Tampilan Satu Pintu Manajemen Wadah Finansial (Master Portofolio)
     */
    public function index(Request $request)
    {
        $currentWorkDay = WorkDay::where('work_date', now()->toDateString())->first();

        // 1. Wadah Simpanan & Aset (Bank, E-Wallet, Kas Fisik)
        $assetTypes = [AccountType::BANK, AccountType::EWALLET, AccountType::CASH];

        $activeAccounts = Account::whereIn('type', $assetTypes)
            ->where('is_active', true)
            ->withCount('ledgerEntries')
            ->with(['activeDeductions'])
            ->orderBy('name')
            ->get()
            ->sortBy(fn($a) => match($a->type->value ?? $a->type) {
                'cash' => 1,
                'bank' => 2,
                'ewallet' => 3,
                default => 4,
            });

        $inactiveAccounts = Account::whereIn('type', $assetTypes)
            ->where('is_active', false)
            ->withCount('ledgerEntries')
            ->orderBy('name')
            ->get();

        $totalAssetBalance = (int) $activeAccounts->sum('current_balance');

        // 2. Wadah Liabilitas & Kredit (PayLater & Pinjaman)
        $debts = Debt::with(['installments'])
            ->orderBy('status', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Tandai tiap utang apakah aman untuk dihapus (belum ada pembayaran)
        $debts->each(function ($debt) {
            $paidCount = $debt->installments->where('amount_paid', '>', 0)->count();
            $debt->can_be_deleted = ($paidCount === 0);
            $debt->paid_sum = $debt->installments->sum('amount_paid');
        });

        $activeDebts = $debts->where('status', 'active');
        $totalDebtBalance = (int) $activeDebts->sum('remaining_amount');

        // Wadah PayLater (Plafon Kredit, Terpakai, Sisa Limit)
        $paylaterProviders = PaylaterProvider::with(['debts.installments'])->get();
        $paylaterProviders->each(function ($provider) {
            $provider->can_be_deleted = $provider->debts->isEmpty();
        });

        $activePaylaters = $paylaterProviders->where('is_active', true);
        $totalCreditLimit = (int) $activePaylaters->sum('credit_limit');
        $totalUsedCredit = (int) $activePaylaters->sum('used_credit');
        $totalAvailableCredit = (int) $activePaylaters->sum('available_credit');

        // 3. Ringkasan Portofolio
        $netWorth = $totalAssetBalance - $totalDebtBalance;

        return view('pages.wallets.index', compact(
            'currentWorkDay',
            'activeAccounts',
            'inactiveAccounts',
            'debts',
            'paylaterProviders',
            'totalCreditLimit',
            'totalUsedCredit',
            'totalAvailableCredit',
            'totalAssetBalance',
            'totalDebtBalance',
            'netWorth'
        ));
    }
}
