<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\WorkDay;
use App\Services\CodOrderService;
use App\Services\FriendDebtSettlementService;
use App\Services\InitialBalanceService;
use App\Services\LedgerService;
use App\Services\LoanDisbursementService;
use App\Services\OpenWorkDayService;
use App\Services\QuickFuelService;
use App\Services\QuickParkingService;
use App\Services\SPayLaterPaymentService;
use App\Services\TransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OperationalWebController extends Controller
{
    /**
     * Buka Hari Kerja Baru & Set Saldo Awal Kas Fisik
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
     * Tambah Akun Baru (Rekening Bank, E-Wallet, Kartu)
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:accounts,name',
            'type' => 'required|in:bank,ewallet,cash',
            'initial_balance' => 'required|integer|min:0',
        ]);

        Account::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'initial_balance' => (int) $validated['initial_balance'],
            'current_balance' => (int) $validated['initial_balance'],
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', "Akun/Rekening baru {$validated['name']} berhasil ditambahkan dengan modal awal Rp" . number_format($validated['initial_balance'], 0, ',', '.'));
    }

    /**
     * Set / Koreksi Saldo Awal Akun
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
     * Catat Parkir Motor (Mandiri vs Ditanggung Resto)
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
     * Pengeluaran Pribadi Sehari-hari (Makan, Pulsa, Belanja)
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
            ['name' => 'Beban Kebutuhan Sehari-hari'],
            [
                'type' => AccountType::EXPENSE,
                'current_balance' => 0,
                'initial_balance' => 0,
                'is_active' => true,
            ]
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
            metadata: ['type' => 'personal_lifestyle_expense']
        );

        return redirect()->back()->with('success', 'Pengeluaran kebutuhan sehari-hari berhasil dicatat.');
    }

    /**
     * Pemasukan Pribadi di Luar Driver (Project, Jual Barang, Hadiah)
     */
    public function storePersonalIncome(
        Request $request,
        LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'destination_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $dest = Account::findOrFail($validated['destination_account_id']);
        $incomeAccount = Account::firstOrCreate(
            ['name' => 'Pendapatan Lain-lain / Pribadi'],
            [
                'type' => AccountType::INCOME,
                'current_balance' => 0,
                'initial_balance' => 0,
                'is_active' => true,
            ]
        );

        $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'income',
            description: $validated['description'],
            transactionTime: now(),
            entries: [
                ['account_id' => $dest->id, 'entry_type' => EntryType::DEBIT, 'amount' => (int) $validated['amount']],
                ['account_id' => $incomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => (int) $validated['amount']],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'personal_external_income']
        );

        return redirect()->back()->with('success', 'Pemasukan pribadi berhasil dicatat ke saldo ' . $dest->name);
    }

    /**
     * Buka / Ajukan Pinjaman Baru / Pinjam Teman / Pinjol
     */
    public function storeDebt(
        Request $request,
        LoanDisbursementService $disbursementService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'name' => 'required|string|max:255',
            'platform' => 'required|in:spaylater,seabank_pinjam,gopay_pinjam,bank,person,other',
            'borrower_name' => 'nullable|string|max:255',
            'principal_amount' => 'required|integer|min:1000',
            'fee_amount' => 'required|integer|min:0',
            'tenor_months' => 'required|integer|min:1|max:36',
            'destination_account_id' => 'required|exists:accounts,id',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $destinationAccount = Account::findOrFail($validated['destination_account_id']);

        $totalObligation = (int) $validated['principal_amount'] + (int) $validated['fee_amount'];

        $debt = Debt::create([
            'name' => $validated['name'],
            'platform' => $validated['platform'],
            'borrower_name' => $validated['borrower_name'] ?: 'Self',
            'principal_amount' => (int) $validated['principal_amount'],
            'fee_amount' => (int) $validated['fee_amount'],
            'total_obligation' => $totalObligation,
            'tenor_months' => (int) $validated['tenor_months'],
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Disburse pinjaman masuk ke rekening/kas
        $disbursementService->disburseLoan(
            workDay: $workDay,
            debt: $debt,
            destinationAccount: $destinationAccount,
            netReceivedAmount: (int) $validated['principal_amount'],
            feeAmount: (int) $validated['fee_amount'],
            disbursedAt: now(),
            dueDayOfMonth: 5
        );

        return redirect()->back()->with('success', "Pinjaman {$debt->name} berhasil dicatat & dana bersih masuk ke {$destinationAccount->name}");
    }

    /**
     * Belanja Online Menggunakan SPayLater (Beli Barang Dulu, Bayar Nanti)
     */
    public function storeSPayLaterPurchase(
        Request $request,
        LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'item_name' => 'required|string|max:255',
            'total_price' => 'required|integer|min:1000',
            'tenor_months' => 'required|integer|min:1|max:24',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $totalPrice = (int) $validated['total_price'];
        $tenor = (int) $validated['tenor_months'];

        $debt = Debt::create([
            'name' => 'Belanja Shopee SPayLater: ' . $validated['item_name'],
            'platform' => 'spaylater',
            'borrower_name' => 'Self',
            'principal_amount' => $totalPrice,
            'fee_amount' => 0,
            'total_obligation' => $totalPrice,
            'tenor_months' => $tenor,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $shoppingExpenseAccount = Account::firstOrCreate(
            ['name' => 'Beban Belanja Konsumtif Pribadi'],
            [
                'type' => AccountType::EXPENSE,
                'current_balance' => 0,
                'initial_balance' => 0,
                'is_active' => true,
            ]
        );

        $spaylaterLiabilityAccount = Account::firstOrCreate(
            ['name' => 'Kewajiban Utang - ' . $debt->name],
            [
                'type' => AccountType::DEBT,
                'current_balance' => 0,
                'initial_balance' => 0,
                'is_active' => true,
            ]
        );

        $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: "Belanja SPayLater: {$validated['item_name']} ({$tenor}x Cicilan)",
            transactionTime: now(),
            entries: [
                ['account_id' => $spaylaterLiabilityAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $totalPrice],
                ['account_id' => $shoppingExpenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $totalPrice],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'spaylater_purchase', 'debt_id' => $debt->id]
        );

        // Buat jadwal cicilan per bulan
        $monthlyAmount = intdiv($totalPrice, $tenor);
        $remainder = $totalPrice % $tenor;

        for ($i = 1; $i <= $tenor; $i++) {
            $dueDate = now()->addMonths($i)->setDay(5);
            $amount = ($i === 1) ? $monthlyAmount + $remainder : $monthlyAmount;

            DebtInstallment::create([
                'debt_id' => $debt->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'amount_due' => $amount,
                'amount_paid' => 0,
                'status' => 'unpaid',
            ]);
        }

        return redirect()->back()->with('success', "Belanja SPayLater {$validated['item_name']} berhasil dicatat! Jadwal cicilan telah dibuat.");
    }

    /**
     * Bayar Cicilan SPayLater / Pinjaman
     */
    public function payInstallment(
        Request $request,
        SPayLaterPaymentService $paymentService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'installment_id' => 'required|exists:debt_installments,id',
            'payment_account_id' => 'required|exists:accounts,id',
            'actual_paid' => 'required|integer|min:1',
            'promo_discount' => 'nullable|integer|min:0',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $installment = DebtInstallment::findOrFail($validated['installment_id']);
        $account = Account::findOrFail($validated['payment_account_id']);

        $paymentService->payInstallment(
            workDay: $workDay,
            installment: $installment,
            paymentAccount: $account,
            actualPaidAmount: (int) $validated['actual_paid'],
            promoDiscountAmount: (int) ($validated['promo_discount'] ?? 0)
        );

        return redirect()->back()->with('success', "Cicilan #{$installment->installment_number} {$installment->debt->name} berhasil dibayar lunas.");
    }

    /**
     * Terima Pelunasan Utang dari Teman
     */
    public function settleFriendDebt(
        Request $request,
        FriendDebtSettlementService $settlementService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'debt_id' => 'required|exists:debts,id',
            'receiving_account_id' => 'required|exists:accounts,id',
            'received_amount' => 'required|integer|min:1',
            'forgive_remainder' => 'nullable|boolean',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $debt = Debt::findOrFail($validated['debt_id']);
        $receivingAccount = Account::findOrFail($validated['receiving_account_id']);

        $settlementService->settleFriendDebt(
            workDay: $workDay,
            debt: $debt,
            receivingAccount: $receivingAccount,
            receivedAmount: (int) $validated['received_amount'],
            forgiveRemainder: $request->boolean('forgive_remainder')
        );

        return redirect()->back()->with('success', "Pembayaran dari {$debt->borrower_name} berhasil dicatat masuk ke {$receivingAccount->name}.");
    }
}
