<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\CodOrderService;
use App\Services\InitialBalanceService;
use App\Services\OpenWorkDayService;
use App\Services\PersonalExpenseService;
use App\Services\QuickFuelService;
use App\Services\QuickParkingService;
use App\Services\TransferService;
use App\Services\LedgerService;
use App\Enums\EntryType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OperationalWebController extends Controller
{
    /**
     * Buka Hari Kerja Baru & Set Saldo Awal (Modal)
     */
    public function openWorkDay(
        Request $request,
        OpenWorkDayService $openWorkDayService,
        InitialBalanceService $initialBalanceService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_date' => 'required|date',
            'cash_initial_balance' => 'nullable|integer|min:0',
        ]);

        $workDate = Carbon::parse($validated['work_date']);
        $workDay = $openWorkDayService->openWorkDay($workDate);

        if (isset($validated['cash_initial_balance'])) {
            $cashAccount = Account::where('name', 'Cash')->first();
            if ($cashAccount) {
                if ($cashAccount->initial_balance == 0) {
                    $initialBalanceService->setInitialBalance($cashAccount, (int) $validated['cash_initial_balance']);
                } else {
                    $initialBalanceService->correctInitialBalance(
                        $cashAccount,
                        (int) $validated['cash_initial_balance'],
                        "Set modal awal fisik shift tanggal {$workDate->toDateString()}"
                    );
                }
            }
        }

        return redirect()->back()->with('success', "Hari kerja {$workDate->toDateString()} berhasil dibuka!");
    }

    /**
     * Set / Koreksi Saldo Awal Akun Apapun
     */
    public function setAccountInitialBalance(
        Request $request,
        InitialBalanceService $initialBalanceService
    ): RedirectResponse {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|integer|min:0',
            'reason' => 'nullable|string|min:5',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        if ($account->initial_balance == 0) {
            $initialBalanceService->setInitialBalance($account, (int) $validated['amount']);
        } else {
            $reason = $validated['reason'] ?? "Koreksi saldo awal akun {$account->name}";
            $initialBalanceService->correctInitialBalance($account, (int) $validated['amount'], $reason);
        }

        return redirect()->back()->with('success', "Saldo awal akun {$account->name} berhasil diperbarui.");
    }

    /**
     * Catat Beli Bensin
     */
    public function storeFuel(
        Request $request,
        QuickFuelService $fuelService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'source_account_id' => 'required|exists:accounts,id',
            'vendor_type' => 'required|in:spbu,madura',
            'amount' => 'required|integer|min:1000',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $account = Account::findOrFail($validated['source_account_id']);

        $fuelService->recordFuel(
            workDay: $workDay,
            sourceAccount: $account,
            vendorType: $validated['vendor_type'],
            amount: (int) $validated['amount']
        );

        return redirect()->back()->with('success', 'Transaksi pembelian bensin berhasil dicatat.');
    }

    /**
     * Catat Parkir Motor
     */
    public function storeParking(
        Request $request,
        QuickParkingService $parkingService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'amount' => 'required|integer|min:0',
            'restaurant_name' => 'nullable|string|max:255',
            'is_covered_by_resto' => 'nullable|boolean',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $isCovered = $request->boolean('is_covered_by_resto');

        $parkingService->recordParking(
            workDay: $workDay,
            amount: (int) $validated['amount'],
            restaurantName: $validated['restaurant_name'] ?? null,
            isCoveredByResto: $isCovered
        );

        $msg = $isCovered 
            ? 'Parkir gratis (ditanggung resto) berhasil dicatat tanpa memotong uang kas.' 
            : 'Parkir mandiri berhasil dicatat.';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Catat Pesanan COD ShopeeFood
     */
    public function storeCod(
        Request $request,
        CodOrderService $codService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'order_number' => 'nullable|string|max:100',
            'bill_amount' => 'required|integer|min:1',
            'received_amount' => 'required|integer|gte:bill_amount',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $codService->recordCodOrder(
            workDay: $workDay,
            billAmount: (int) $validated['bill_amount'],
            receivedAmount: (int) $validated['received_amount'],
            orderNumber: $validated['order_number'] ?? null
        );

        return redirect()->back()->with('success', 'Pesanan COD berhasil dicatat & uang fisik tercatat di kas.');
    }

    /**
     * Transfer Antar Rekening
     */
    public function storeTransfer(
        Request $request,
        TransferService $transferService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'source_account_id' => 'required|exists:accounts,id|different:destination_account_id',
            'destination_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|integer|min:1',
            'admin_fee' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $source = Account::findOrFail($validated['source_account_id']);
        $dest = Account::findOrFail($validated['destination_account_id']);

        $transferService->transfer(
            workDay: $workDay,
            sourceAccount: $source,
            destinationAccount: $dest,
            amount: (int) $validated['amount'],
            adminFee: (int) ($validated['admin_fee'] ?? 0),
            note: $validated['note'] ?? null
        );

        return redirect()->back()->with('success', 'Transfer antar rekening berhasil dibukukan.');
    }

    /**
     * Pengeluaran Pribadi (Makan, dll)
     */
    public function storePersonalExpense(
        Request $request,
        LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'source_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $source = Account::findOrFail($validated['source_account_id']);
        $expenseAccount = Account::firstOrCreate(
            ['name' => 'Beban Makanan & Minuman Driver'],
            ['type' => 'expense', 'current_balance' => 0]
        );

        $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $validated['description'],
            transactionTime: now(),
            entries: [
                ['account_id' => $source->id, 'entry_type' => EntryType::CREDIT, 'amount' => (int) $validated['amount']],
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => (int) $validated['amount']],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'personal_on_beat_expense']
        );

        return redirect()->back()->with('success', 'Pengeluaran pribadi berhasil dicatat.');
    }
}
