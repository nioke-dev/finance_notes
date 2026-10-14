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
        // 1. Utang Saya (PayLater / Pinjaman Bank / Lainnya yang bukan talangan teman)
        $myDebts = Debt::active()
            ->where(function ($query) {
                $query->whereIn('platform', ['spaylater', 'seabank_pinjam', 'gopay_pinjam', 'bank', 'other'])
                    ->where(function ($sub) {
                        $sub->whereNull('borrower_name')->orWhere('borrower_name', 'Self');
                    });
            })
            ->with(['installments' => function ($q) {
                $q->orderBy('installment_number', 'asc');
            }])
            ->orderBy('started_at', 'desc')
            ->get();

        // 2. Piutang Teman (Dana yang dipinjam teman atau platform person / borrower_name terisi selain Self)
        $friendDebts = Debt::active()
            ->where(function ($query) {
                $query->where('platform', 'person')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('borrower_name')->where('borrower_name', '!=', 'Self');
                    });
            })
            ->with(['installments' => function ($q) {
                $q->orderBy('installment_number', 'asc');
            }])
            ->orderBy('started_at', 'desc')
            ->get();

        // Jika ada debt bertipe payable / receivable eksplisit atau data uji umum
        if ($myDebts->isEmpty() && $friendDebts->isEmpty()) {
            $myDebts = Debt::active()->whereNull('borrower_name')->with('installments')->get();
            $friendDebts = Debt::active()->whereNotNull('borrower_name')->with('installments')->get();
        }

        $totalPayable = (int) $myDebts->sum('remaining_amount');
        $totalReceivable = (int) $friendDebts->sum('remaining_amount');

        // Cicilan jatuh tempo dalam 30 hari ke depan
        $upcomingInstallments = DebtInstallment::with('debt')
            ->where('status', 'unpaid')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->orderBy('due_date', 'asc')
            ->get();

        $paymentAccounts = Account::where('is_active', true)->orderBy('name', 'asc')->get();

        return view('pages.debts.index', compact(
            'myDebts',
            'friendDebts',
            'totalPayable',
            'totalReceivable',
            'upcomingInstallments',
            'paymentAccounts'
        ));
    }
}
