<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use Illuminate\Http\Request;

class DebtWebController extends Controller
{
    public function index(Request $request)
    {
        // 1. Tagihan PayLater (SPayLater, TikTok PayLater, Kredivo PayLater)
        $paylaterDebts = Debt::active()
            ->payLater()
            ->with(['installments' => function ($q) {
                $q->orderBy('installment_number', 'asc');
            }])
            ->orderBy('started_at', 'desc')
            ->get();

        // 2. Pinjaman Tunai / KTA (SPinjam, SeaBank Pinjam, GoPay Pinjam, Superbank Pinjam, Kredivo Pinjam, Bank)
        $cashLoans = Debt::active()
            ->cashLoan()
            ->with(['installments' => function ($q) {
                $q->orderBy('installment_number', 'asc');
            }])
            ->orderBy('started_at', 'desc')
            ->get();

        // 3. Piutang Teman (Dana kas yang dipinjamkan ke teman / talangan)
        $friendDebts = Debt::active()
            ->friendLoan()
            ->with(['installments' => function ($q) {
                $q->orderBy('installment_number', 'asc');
            }])
            ->orderBy('started_at', 'desc')
            ->get();

        // Gabungan semua utang saya (untuk kompatibilitas)
        $myDebts = $paylaterDebts->concat($cashLoans);

        // Jika data uji umum belum memiliki platform spesifik
        if ($myDebts->isEmpty() && $friendDebts->isEmpty()) {
            $myDebts = Debt::active()->whereNull('borrower_name')->with('installments')->get();
            $friendDebts = Debt::active()->whereNotNull('borrower_name')->with('installments')->get();
        }

        $totalPayLater = (int) $paylaterDebts->sum('remaining_amount');
        $totalCashLoans = (int) $cashLoans->sum('remaining_amount');
        $totalPayable = (int) $myDebts->sum('remaining_amount');
        $totalReceivable = (int) $friendDebts->sum('remaining_amount');

        // Cicilan jatuh tempo dalam 30 hari ke depan
        $upcomingInstallments = DebtInstallment::with('debt')
            ->where('status', 'unpaid')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->orderBy('due_date', 'asc')
            ->get();

        $paymentAccounts = Account::where('is_active', true)->orderBy('name', 'asc')->get();
        $currentWorkDay = \App\Models\WorkDay::whereDate('work_date', now()->toDateString())->first() 
            ?? \App\Models\WorkDay::latest('work_date')->first();

        return view('pages.debts.index', compact(
            'paylaterDebts',
            'cashLoans',
            'myDebts',
            'friendDebts',
            'totalPayLater',
            'totalCashLoans',
            'totalPayable',
            'totalReceivable',
            'upcomingInstallments',
            'paymentAccounts',
            'currentWorkDay'
        ));
    }
}
