<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActiveCodOrder;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\FinancialGoal;
use App\Models\WorkDay;
use App\Services\ActiveCodOrderService;
use App\Services\CodOrderService;
use App\Services\DigitalReconciliationService;
use App\Services\FinancialGoalService;
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
            'day_type' => 'nullable|in:on_bid,day_off',
            'cash_initial_balance' => 'nullable|integer|min:0',
            'bill_100000' => 'nullable|integer|min:0',
            'bill_50000' => 'nullable|integer|min:0',
            'bill_20000' => 'nullable|integer|min:0',
            'bill_10000' => 'nullable|integer|min:0',
            'bill_5000' => 'nullable|integer|min:0',
            'bill_2000' => 'nullable|integer|min:0',
            'bill_1000' => 'nullable|integer|min:0',
            'coin_1000' => 'nullable|integer|min:0',
            'coin_500' => 'nullable|integer|min:0',
            'coin_200' => 'nullable|integer|min:0',
            'coin_100' => 'nullable|integer|min:0',
        ]);

        $workDate = Carbon::parse($validated['work_date']);
        $dayType = $validated['day_type'] ?? WorkDay::TYPE_ON_BID;

        // Validasi Audit Integritas: Hari sebelumnya wajib sudah diinput hitungan fisik kasnya (walau selisih boleh pending)
        $previousDay = WorkDay::where('work_date', '<', $workDate->toDateString())
            ->orderBy('work_date', 'desc')
            ->first();

        if ($previousDay && !$previousDay->isFinal()) {
            $prevDenom = \App\Models\CashDenomination::where('work_day_id', $previousDay->id)->first();
            if (!$prevDenom && !$request->has('force_open')) {
                return redirect()->back()->with('error', "Hari sebelumnya ({$previousDay->work_date->translatedFormat('d M Y')}) belum pernah diinput hitungan fisik kasnya. Harap selesaikan hitungan kas fisik hari kemarin terlebih dahulu di menu Closing.");
            }
        }

        $workDay = $openWorkDayService->openWorkDay($workDate, $dayType);

        // Hitung total dari lembaran & koin
        $calculatedDenom = \App\Models\CashDenomination::calculateSum($validated);
        $totalCashInitial = $calculatedDenom > 0 ? $calculatedDenom : (int) ($validated['cash_initial_balance'] ?? 0);

        // Simpan pecahan ke tabel CashDenomination untuk shift ini jika ada input
        if ($calculatedDenom > 0) {
            $denomRecord = \App\Models\CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
            $denomRecord->fill($validated);
            $denomRecord->total_physical_amount = $calculatedDenom;
            $denomRecord->save();
        }

        if ($totalCashInitial > 0) {
            $cashAccount = Account::where('name', 'Cash')->first();
            if ($cashAccount) {
                if ($cashAccount->initial_balance == 0) {
                    $initialBalanceService->setInitialBalance($cashAccount, $totalCashInitial);
                } else {
                    $initialBalanceService->correctInitialBalance(
                        $cashAccount,
                        $totalCashInitial,
                        "Set modal awal kas fisik shift tanggal {$workDate->toDateString()} via hitungan lembar/koin"
                    );
                }
            }
        }

        $dayLabel = $dayType === WorkDay::TYPE_DAY_OFF ? 'Hari Libur' : 'Hari Kerja / Narik';
        $successMsg = $totalCashInitial > 0
            ? "{$dayLabel} ({$workDate->toDateString()}) berhasil dibuka dengan modal kas fisik Rp" . number_format($totalCashInitial, 0, ',', '.')
            : "{$dayLabel} ({$workDate->toDateString()}) berhasil dibuka. Saldo kas otomatis melanjutkan saldo berjalan sebelumnya.";

        return redirect()->back()->with('success', $successMsg);
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
     * Nonaktifkan / Aktifkan Kembali Rekening Bank atau E-Wallet
     */
    public function toggleAccountActive(Request $request, int $id): RedirectResponse
    {
        $account = Account::findOrFail($id);

        // Jika akan dinonaktifkan, periksa saldo harus Rp0
        if ($account->is_active) {
            if ($account->current_balance != 0) {
                return redirect()->back()->with('error', "Rekening {$account->name} tidak dapat dinonaktifkan karena masih memiliki sisa saldo Rp " . number_format($account->current_balance, 0, ',', '.') . ". Pindahkan atau tarik seluruh saldo terlebih dahulu.");
            }

            $account->update(['is_active' => false]);

            \App\Models\AuditLog::create([
                'auditable_type' => Account::class,
                'auditable_id' => $account->id,
                'action' => 'deactivate_account',
                'old_values' => ['is_active' => true],
                'new_values' => ['is_active' => false],
                'reason' => $request->input('reason', "Menonaktifkan / mengarsipkan rekening {$account->name}"),
            ]);

            return redirect()->back()->with('success', "Rekening {$account->name} berhasil dinonaktifkan / diarsipkan. Seluruh riwayat transaksi masa lalu tetap tersimpan utuh.");
        }

        // Aktifkan kembali
        $account->update(['is_active' => true]);

        \App\Models\AuditLog::create([
            'auditable_type' => Account::class,
            'auditable_id' => $account->id,
            'action' => 'activate_account',
            'old_values' => ['is_active' => false],
            'new_values' => ['is_active' => true],
            'reason' => "Mengaktifkan kembali rekening {$account->name}",
        ]);

        return redirect()->back()->with('success', "Rekening {$account->name} berhasil diaktifkan kembali.");
    }

    /**
     * Proteksi Hapus Akun: Dilarang hard delete jika sudah ada riwayat transaksi
     */
    public function destroyAccount(Request $request, int $id): RedirectResponse
    {
        $account = Account::findOrFail($id);

        if ($account->ledgerEntries()->exists()) {
            return redirect()->back()->with('error', "Wadah/Rekening {$account->name} DILARANG dihapus karena sudah memiliki riwayat transaksi pembukuan. Untuk menutup rekening, gunakan fitur 'Tutup / Arsipkan Rekening'.");
        }

        if ($account->current_balance != 0) {
            return redirect()->back()->with('error', "Wadah/Rekening {$account->name} tidak dapat dihapus karena masih memiliki saldo Rp " . number_format($account->current_balance, 0, ',', '.') . ". Pindahkan saldo terlebih dahulu.");
        }

        $name = $account->name;
        $account->delete();

        \App\Models\AuditLog::create([
            'auditable_type' => Account::class,
            'auditable_id' => $id,
            'action' => 'destroy_account',
            'old_values' => ['name' => $name],
            'new_values' => null,
            'reason' => "Menghapus wadah/rekening kosong {$name} yang belum memiliki transaksi.",
        ]);

        return redirect()->back()->with('success', "Wadah/Rekening {$name} yang belum memiliki riwayat transaksi berhasil dihapus permanen.");
    }

    /**
     * Tambah Wadah PayLater Baru
     */
    public function storePaylaterProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'credit_limit' => 'nullable|integer|min:0',
            'icon' => 'nullable|string|max:20',
        ]);

        $slug = \Illuminate\Support\Str::slug($validated['name'], '_');

        if (\App\Models\PaylaterProvider::where('slug', $slug)->exists()) {
            return redirect()->back()->with('error', "Wadah PayLater {$validated['name']} sudah ada.");
        }

        $provider = \App\Models\PaylaterProvider::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'credit_limit' => (int) ($validated['credit_limit'] ?? 0),
            'icon' => $validated['icon'] ?? '🛍️',
            'is_active' => true,
        ]);

        \App\Models\AuditLog::create([
            'auditable_type' => \App\Models\PaylaterProvider::class,
            'auditable_id' => $provider->id,
            'action' => 'create_paylater_provider',
            'old_values' => null,
            'new_values' => $provider->toArray(),
            'reason' => "Menambahkan wadah kredit PayLater baru {$provider->name}",
        ]);

        return redirect()->back()->with('success', "Wadah PayLater {$provider->name} berhasil ditambahkan!");
    }

    /**
     * Update Limit Plafon Kredit Wadah PayLater
     */
    public function updatePaylaterProviderLimit(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'credit_limit' => 'required|integer|min:0',
        ]);

        $provider = \App\Models\PaylaterProvider::findOrFail($id);
        $oldLimit = $provider->credit_limit;
        $provider->update(['credit_limit' => (int) $validated['credit_limit']]);

        \App\Models\AuditLog::create([
            'auditable_type' => \App\Models\PaylaterProvider::class,
            'auditable_id' => $provider->id,
            'action' => 'update_paylater_limit',
            'old_values' => ['credit_limit' => $oldLimit],
            'new_values' => ['credit_limit' => $provider->credit_limit],
            'reason' => "Memperbarui limit kredit {$provider->name} menjadi Rp " . number_format($provider->credit_limit, 0, ',', '.'),
        ]);

        return redirect()->back()->with('success', "Limit kredit {$provider->name} berhasil diperbarui menjadi Rp " . number_format($provider->credit_limit, 0, ',', '.'));
    }

    /**
     * Nonaktifkan / Aktifkan Kembali Wadah PayLater
     */
    public function togglePaylaterProviderActive(Request $request, int $id): RedirectResponse
    {
        $provider = \App\Models\PaylaterProvider::findOrFail($id);

        if ($provider->is_active && $provider->used_credit > 0) {
            return redirect()->back()->with('error', "Wadah PayLater {$provider->name} tidak dapat dinonaktifkan karena masih memiliki tagihan berjalan sebesar Rp " . number_format($provider->used_credit, 0, ',', '.') . ". Lunasi tagihan terlebih dahulu.");
        }

        $provider->update(['is_active' => !$provider->is_active]);

        $statusLabel = $provider->is_active ? 'diaktifkan kembali' : 'dinonaktifkan / diarsipkan';
        return redirect()->back()->with('success', "Wadah PayLater {$provider->name} berhasil {$statusLabel}.");
    }

    /**
     * Hapus Wadah PayLater (Hanya jika belum pernah ada transaksi tagihan)
     */
    public function destroyPaylaterProvider(Request $request, int $id): RedirectResponse
    {
        $provider = \App\Models\PaylaterProvider::findOrFail($id);

        if ($provider->debts()->exists()) {
            return redirect()->back()->with('error', "Wadah PayLater {$provider->name} DILARANG dihapus karena sudah memiliki riwayat tagihan. Untuk menutupnya, gunakan fitur 'Tutup / Arsipkan Wadah'.");
        }

        $name = $provider->name;
        $provider->delete();

        \App\Models\AuditLog::create([
            'auditable_type' => \App\Models\PaylaterProvider::class,
            'auditable_id' => $id,
            'action' => 'destroy_paylater_provider',
            'old_values' => ['name' => $name],
            'new_values' => null,
            'reason' => "Menghapus wadah PayLater kosong {$name} yang belum memiliki transaksi.",
        ]);

        return redirect()->back()->with('success', "Wadah PayLater {$name} berhasil dihapus permanen.");
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
            'amount' => 'nullable|integer|min:0',
            'reason' => 'nullable|string|min:5',
            'bill_100000' => 'nullable|integer|min:0',
            'bill_50000' => 'nullable|integer|min:0',
            'bill_20000' => 'nullable|integer|min:0',
            'bill_10000' => 'nullable|integer|min:0',
            'bill_5000' => 'nullable|integer|min:0',
            'bill_2000' => 'nullable|integer|min:0',
            'bill_1000' => 'nullable|integer|min:0',
            'coin_1000' => 'nullable|integer|min:0',
            'coin_500' => 'nullable|integer|min:0',
            'coin_200' => 'nullable|integer|min:0',
            'coin_100' => 'nullable|integer|min:0',
        ]);

        $calculatedDenom = \App\Models\CashDenomination::calculateSum($request->all());
        $amount = $calculatedDenom > 0 ? $calculatedDenom : (int) ($validated['amount'] ?? 0);

        $account = Account::findOrFail($validated['account_id']);
        if ($account->initial_balance == 0) {
            $initialBalanceService->setInitialBalance($account, $amount);
        } else {
            $reason = $validated['reason'] ?? "Koreksi saldo awal akun {$account->name}";
            $initialBalanceService->correctInitialBalance($account, $amount, $reason);
        }

        // Jika akun bertipe Kas dan ada rincian pecahan fisik, simpan/update ke shift aktif hari ini jika ada
        if ($account->type === AccountType::CASH && $calculatedDenom > 0) {
            $todayWorkDay = WorkDay::whereDate('work_date', now()->toDateString())->first();
            if ($todayWorkDay) {
                $denom = \App\Models\CashDenomination::firstOrNew(['work_day_id' => $todayWorkDay->id]);
                $denom->fill($request->only([
                    'bill_100000', 'bill_50000', 'bill_20000', 'bill_10000',
                    'bill_5000', 'bill_2000', 'bill_1000',
                    'coin_1000', 'coin_500', 'coin_200', 'coin_100'
                ]));
                $denom->total_physical_amount = $calculatedDenom;
                $denom->save();
            }
        }

        return redirect()->back()->with('success', "Saldo awal akun {$account->name} berhasil diperbarui (Rp" . number_format($amount, 0, ',', '.') . ").");
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
     * Terima Order COD Baru di Jalan (ShopeePay otomatis terpotong untuk talangan resto)
     */
    public function acceptCodOrder(
        Request $request,
        ActiveCodOrderService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'order_code' => 'required|string|max:20',
            'bill_amount' => 'required|integer|min:100',
            'merchant_name' => 'nullable|string|max:255',
            'rounded_bill_amount' => 'nullable|integer|min:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:255',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $order = $service->acceptCodOrder(
            workDay: $workDay,
            orderCode: $validated['order_code'],
            billAmount: (int) $validated['bill_amount'],
            merchantName: $validated['merchant_name'] ?? null,
            roundedBillAmount: !empty($validated['rounded_bill_amount']) ? (int) $validated['rounded_bill_amount'] : null,
            latitude: !empty($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: !empty($validated['longitude']) ? (float) $validated['longitude'] : null,
            notes: $validated['notes'] ?? null
        );

        return redirect()->back()->with('success', "Pesanan COD #{$order->order_code} berhasil diterima. Saldo ShopeePay otomatis terpotong Rp " . number_format($order->bill_amount, 0, ',', '.') . ".");
    }

    /**
     * Selesaikan Pesanan COD di Lokasi Customer (Kalkulator Sederhana & Masuk Kas/QRIS)
     */
    public function completeCodOrder(
        Request $request,
        int $id,
        ActiveCodOrderService $service
    ): RedirectResponse {
        $order = ActiveCodOrder::findOrFail($id);

        $validated = $request->validate([
            'received_amount' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,qris',
            'customer_gave_amount' => 'nullable|integer|min:0',
            'change_returned' => 'nullable|integer|min:0',
            'settlement_account_id' => 'nullable|exists:accounts,id',
            'parking_reimbursed' => 'nullable|integer|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:255',
        ]);

        $completed = $service->completeCodOrder(
            order: $order,
            receivedAmount: (int) $validated['received_amount'],
            paymentMethod: $validated['payment_method'],
            customerGaveAmount: !empty($validated['customer_gave_amount']) ? (int) $validated['customer_gave_amount'] : null,
            changeReturned: isset($validated['change_returned']) ? (int) $validated['change_returned'] : null,
            settlementAccountId: !empty($validated['settlement_account_id']) ? (int) $validated['settlement_account_id'] : null,
            parkingReimbursed: (int) ($validated['parking_reimbursed'] ?? 0),
            latitude: !empty($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: !empty($validated['longitude']) ? (float) $validated['longitude'] : null,
            notes: $validated['notes'] ?? null
        );

        $methodLabel = $completed->payment_method === 'cash' ? 'Kas Dompet' : 'Rekening Digital (QRIS)';
        return redirect()->back()->with('success', "Pesanan COD #{$completed->order_code} selesai diantar! Uang Rp " . number_format($completed->received_amount, 0, ',', '.') . " berhasil masuk ke {$methodLabel}.");
    }

    /**
     * Batalkan Pesanan COD & Pulihkan Saldo ShopeePay
     */
    public function cancelCodOrder(
        Request $request,
        int $id,
        ActiveCodOrderService $service
    ): RedirectResponse {
        $order = ActiveCodOrder::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $cancelled = $service->cancelCodOrder($order, $validated['reason']);

        return redirect()->back()->with('success', "Pesanan COD #{$cancelled->order_code} telah dibatalkan & saldo ShopeePay telah dipulihkan.");
    }

    /**
     * Catat Pesanan COD ShopeeFood (Legacy Langsung)
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
            'source_account_id' => 'required|string',
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'tenor_months' => 'nullable|integer|min:1|max:24',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        // Jika sumber pembayaran adalah Wadah PayLater
        if (str_starts_with($validated['source_account_id'], 'paylater_')) {
            $slug = substr($validated['source_account_id'], 9);
            $provider = \App\Models\PaylaterProvider::where('slug', $slug)->first();
            $providerName = $provider ? $provider->name : ucfirst(str_replace('_', ' ', $slug));

            $tenor = (int) ($validated['tenor_months'] ?? 1);
            $amount = (int) $validated['amount'];
            $dueDate = now()->addMonths($tenor)->endOfMonth();

            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $slug, $amount, $tenor) {
                $debt = Debt::create([
                    'name' => 'Belanja PayLater: ' . $validated['description'],
                    'platform' => $slug,
                    'borrower_name' => 'Self',
                    'principal_amount' => $amount,
                    'fee_amount' => 0,
                    'total_obligation' => $amount,
                    'tenor_months' => $tenor,
                    'status' => 'active',
                    'started_at' => now(),
                ]);

                $monthlyInstallment = (int) ceil($amount / $tenor);
                for ($i = 1; $i <= $tenor; $i++) {
                    DebtInstallment::create([
                        'debt_id' => $debt->id,
                        'installment_number' => $i,
                        'due_date' => now()->addMonths($i)->endOfMonth(),
                        'amount_due' => ($i === $tenor) ? ($amount - ($monthlyInstallment * ($tenor - 1))) : $monthlyInstallment,
                        'amount_paid' => 0,
                        'status' => 'unpaid',
                    ]);
                }
            });

            return redirect()->back()->with('success', "Belanja {$validated['description']} berhasil dicatat via {$providerName} (Tenor {$tenor} Bulan, Jatuh Tempo {$dueDate->translatedFormat('d M Y')}) tanpa memotong saldo kas/bank saat ini.");
        }

        $sourceId = str_replace('acc_', '', $validated['source_account_id']);
        $source = Account::findOrFail($sourceId);
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
     * Catat Biaya Serba-serbi & Insidental Lapangan (Tambal ban, lakban, lampu sen, dsb)
     */
    public function storeFieldIncidentalExpense(
        Request $request,
        LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|integer|min:500',
            'source_account_id' => 'nullable|exists:accounts,id',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        
        $sourceAccount = !empty($validated['source_account_id'])
            ? Account::findOrFail($validated['source_account_id'])
            : Account::where('name', 'Cash')->firstOrFail();

        $expenseAccount = Account::firstOrCreate(
            ['name' => 'Beban Serba-serbi & Insidental Lapangan'],
            [
                'type' => AccountType::EXPENSE,
                'current_balance' => 0,
                'initial_balance' => 0,
                'is_active' => true,
            ]
        );

        $amount = (int) $validated['amount'];
        $desc = "Biaya Serba-serbi: " . trim($validated['description']);

        $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $desc,
            transactionTime: now(),
            entries: [
                ['account_id' => $sourceAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $amount],
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $amount],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'field_incidental_expense',
                'description' => $validated['description'],
            ]
        );

        return redirect()->back()->with('success', "Biaya serba-serbi '{$validated['description']}' (Rp " . number_format($amount, 0, ',', '.') . ") berhasil dicatat memotong {$sourceAccount->name}.");
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
            'platform' => 'required|string|max:50',
            'custom_platform' => 'nullable|string|max:50',
            'borrower_name' => 'nullable|string|max:255',
            'principal_amount' => 'required|integer|min:1000',
            'fee_amount' => 'required|integer|min:0',
            'tenor_months' => 'required|integer|min:1|max:36',
            'destination_account_id' => 'required|exists:accounts,id',
            'due_day_of_month' => 'nullable|integer|min:1|max:31',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $destinationAccount = Account::findOrFail($validated['destination_account_id']);
        $dueDay = (int) ($validated['due_day_of_month'] ?? 5);

        $platform = ($validated['platform'] === 'other' && !empty($validated['custom_platform']))
            ? \Illuminate\Support\Str::slug($validated['custom_platform'], '_')
            : $validated['platform'];

        $totalObligation = (int) $validated['principal_amount'] + (int) $validated['fee_amount'];

        $debt = Debt::create([
            'name' => $validated['name'],
            'platform' => $platform,
            'borrower_name' => $validated['borrower_name'] ?? 'Self',
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
            dueDayOfMonth: $dueDay
        );

        return redirect()->back()->with('success', "Pinjaman {$debt->name} berhasil dicatat & dana bersih masuk ke {$destinationAccount->name}");
    }

    /**
     * Catat Saldo Utang / Tagihan Berjalan Masa Lalu (Tanpa Pencairan Kas Baru)
     */
    public function storeExistingDebt(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|string|max:50',
            'custom_platform' => 'nullable|string|max:50',
            'total_obligation' => 'required|integer|min:1000',
            'due_date' => 'required|date',
            'tenor_months' => 'nullable|integer|min:1|max:36',
            'notes' => 'nullable|string|max:255',
        ]);

        $tenor = (int) ($validated['tenor_months'] ?? 1);
        $totalObligation = (int) $validated['total_obligation'];
        $dueDate = Carbon::parse($validated['due_date']);

        $platform = (in_array($validated['platform'], ['other', 'other_paylater']) && !empty($validated['custom_platform']))
            ? \Illuminate\Support\Str::slug($validated['custom_platform'], '_')
            : $validated['platform'];

        $debt = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $platform, $totalObligation, $tenor, $dueDate) {
            $debt = Debt::create([
                'name' => $validated['name'],
                'platform' => $platform,
                'borrower_name' => 'Self',
                'principal_amount' => $totalObligation,
                'fee_amount' => 0,
                'total_obligation' => $totalObligation,
                'tenor_months' => $tenor,
                'status' => 'active',
                'started_at' => now(),
            ]);

            // Catat akun kewajiban di neraca
            $liabilityAccountName = 'Kewajiban Utang - ' . $debt->name;
            Account::firstOrCreate(
                ['name' => $liabilityAccountName],
                [
                    'type' => AccountType::DEBT,
                    'current_balance' => $totalObligation,
                    'initial_balance' => $totalObligation,
                    'is_active' => true,
                ]
            );

            // Buat jadwal cicilan / tagihan
            $monthlyAmount = intdiv($totalObligation, $tenor);
            $remainder = $totalObligation % $tenor;

            for ($i = 1; $i <= $tenor; $i++) {
                $installmentDueDate = $i === 1 ? $dueDate : $dueDate->copy()->addMonths($i - 1);
                $amountDue = ($i === 1) ? $monthlyAmount + $remainder : $monthlyAmount;

                DebtInstallment::create([
                    'debt_id' => $debt->id,
                    'installment_number' => $i,
                    'due_date' => $installmentDueDate,
                    'amount_due' => $amountDue,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                ]);
            }

            \App\Models\AuditLog::create([
                'auditable_type' => Debt::class,
                'auditable_id' => $debt->id,
                'action' => 'debt_registered',
                'old_values' => null,
                'new_values' => $debt->toArray(),
                'reason' => "Pencatatan saldo tagihan utang berjalan {$debt->name} (Rp " . number_format($totalObligation, 0, ',', '.') . ") jatuh tempo {$dueDate->format('d/m/Y')}.",
            ]);

            return $debt;
        });

        return redirect()->back()->with('success', "Tagihan {$debt->getPlatformLabel()} ({$debt->name}) sebesar Rp " . number_format($totalObligation, 0, ',', '.') . " berhasil dicatat dengan jatuh tempo {$dueDate->translatedFormat('d M Y')}!");
    }

    /**
     * Hapus Tagihan PayLater / Pinjaman (Hanya jika belum ada pembayaran cicilan sama sekali)
     */
    public function destroyDebt(Request $request, int $id): RedirectResponse
    {
        $debt = Debt::with('installments')->findOrFail($id);

        // Cek apakah sudah pernah ada cicilan yang dibayar
        $paidAmount = (int) $debt->installments()->sum('amount_paid');
        if ($paidAmount > 0) {
            return redirect()->back()->with('error', "Tagihan {$debt->name} DILARANG dihapus karena sudah memiliki riwayat pembayaran cicilan sebesar Rp " . number_format($paidAmount, 0, ',', '.') . ". Selesaikan pembayaran cicilan hingga lunas.");
        }

        // Cek apakah pinjaman tunai ini pernah mencairkan dana riil ke kas/bank
        $hasDisbursementTx = \App\Models\Transaction::whereJsonContains('metadata->debt_id', $debt->id)
            ->orWhere('metadata->debt_id', (string) $debt->id)
            ->exists();

        if ($hasDisbursementTx) {
            return redirect()->back()->with('error', "Pinjaman {$debt->name} tidak dapat dihapus langsung karena dana pokok telah dicairkan ke rekening Anda. Selesaikan pelunasan pinjaman.");
        }

        $debtName = $debt->name;
        $platform = $debt->getPlatformLabel();

        \Illuminate\Support\Facades\DB::transaction(function () use ($debt, $debtName, $platform) {
            $debt->installments()->delete();
            $debt->delete();

            \App\Models\AuditLog::create([
                'auditable_type' => Debt::class,
                'auditable_id' => $debt->id,
                'action' => 'delete_unpaid_debt',
                'old_values' => ['name' => $debtName, 'platform' => $platform],
                'new_values' => null,
                'reason' => 'Membatalkan / menghapus tagihan yang salah input dan belum memiliki riwayat pembayaran.',
            ]);
        });

        return redirect()->back()->with('success', "Tagihan {$debtName} ({$platform}) yang belum memiliki riwayat pembayaran berhasil dihapus.");
    }

    /**
     * Tambah Langganan / Tagihan Rutin Bulanan
     */
    public function storeSubscription(Request $request, \App\Services\SubscriptionService $subService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|in:work_essential,living_expense,utilities,entertainment,other',
            'amount' => 'required|integer|min:1000',
            'due_day_of_month' => 'required|integer|min:1|max:31',
            'payment_account_id' => 'nullable|exists:accounts,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $sub = $subService->createSubscription($validated);

        return redirect()->back()->with('success', "Tagihan rutin '{$sub->name}' (Rp " . number_format($sub->amount, 0, ',', '.') . "/bln) berhasil didaftarkan!");
    }

    /**
     * Bayar Langganan / Tagihan Rutin
     */
    public function paySubscription(Request $request, \App\Services\SubscriptionService $subService): RedirectResponse
    {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'subscription_id' => 'required|exists:subscriptions,id',
            'payment_account_id' => 'required|exists:accounts,id',
            'actual_amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $subscription = \App\Models\Subscription::findOrFail($validated['subscription_id']);
        $paymentAccount = Account::findOrFail($validated['payment_account_id']);

        $subService->paySubscription(
            workDay: $workDay,
            subscription: $subscription,
            paymentAccount: $paymentAccount,
            actualAmount: (int) $validated['actual_amount'],
            notes: $validated['notes'] ?? null
        );

        return redirect()->back()->with('success', "Tagihan rutin '{$subscription->name}' berhasil dibayar menggunakan {$paymentAccount->name}!");
    }

    /**
     * Toggle status aktif tagihan rutin
     */
    public function toggleSubscription(int $id): RedirectResponse
    {
        $sub = \App\Models\Subscription::findOrFail($id);
        $sub->update(['is_active' => !$sub->is_active]);

        $statusMsg = $sub->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Tagihan rutin '{$sub->name}' berhasil {$statusMsg}.");
    }

    /**
     * Belanja Online Menggunakan PayLater (SPayLater / TikTok / Kredivo)
     */
    public function storeSPayLaterPurchase(
        Request $request,
        LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'platform' => 'nullable|string|max:50',
            'custom_platform' => 'nullable|string|max:50',
            'item_name' => 'required|string|max:255',
            'total_price' => 'required|integer|min:1000',
            'tenor_months' => 'required|integer|min:1|max:24',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $rawPlatform = $validated['platform'] ?? 'spaylater';
        $platform = (in_array($rawPlatform, ['other', 'other_paylater']) && !empty($validated['custom_platform']))
            ? \Illuminate\Support\Str::slug($validated['custom_platform'], '_')
            : $rawPlatform;

        $platformLabel = match($platform) {
            'gopay_later' => 'GoPay Later',
            'tiktok_paylater' => 'TikTok PayLater',
            'kredivo_paylater' => 'Kredivo PayLater',
            'akulaku_paylater' => 'Akulaku PayLater',
            'indodana_paylater' => 'Indodana PayLater',
            'spaylater' => 'Shopee SPayLater',
            default => ucfirst(str_replace('_', ' ', $platform)),
        };

        $totalPrice = (int) $validated['total_price'];
        $tenor = (int) $validated['tenor_months'];

        $debt = Debt::create([
            'name' => "Belanja {$platformLabel}: " . $validated['item_name'],
            'platform' => $platform,
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

        $paylaterLiabilityAccount = Account::firstOrCreate(
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
            description: "Belanja {$platformLabel}: {$validated['item_name']} ({$tenor}x Cicilan)",
            transactionTime: now(),
            entries: [
                ['account_id' => $paylaterLiabilityAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $totalPrice],
                ['account_id' => $shoppingExpenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $totalPrice],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'spaylater_purchase', 'debt_id' => $debt->id, 'platform' => $platform]
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

        return redirect()->back()->with('success', "Belanja {$platformLabel} {$validated['item_name']} berhasil dicatat! Tagihan cicilan telah dibuat.");
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

    /**
     * Catat Kas Fisik Dipinjamkan ke Teman / Talangan di Lapangan
     */
    public function lendFriendCash(
        Request $request,
        \App\Services\LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'borrower_name' => 'required|string|max:100',
            'amount' => 'required|integer|min:1000',
            'notes' => 'nullable|string|max:255',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $amount = (int) $validated['amount'];
        $borrowerName = trim($validated['borrower_name']);

        // 1. Akun Piutang Teman (Receivable Asset)
        $receivableAccountName = 'Piutang Teman - ' . $borrowerName;
        $receivableAccount = Account::firstOrCreate(
            ['name' => $receivableAccountName],
            [
                'type' => \App\Enums\AccountType::RECEIVABLE,
                'current_balance' => 0,
                'initial_balance' => 0,
                'is_active' => true,
            ]
        );

        // 2. Buat record Debt dengan status active & borrower_name
        $debt = Debt::create([
            'name' => "Kas Dipinjam: {$borrowerName}",
            'platform' => 'person',
            'borrower_name' => $borrowerName,
            'principal_amount' => $amount,
            'fee_amount' => 0,
            'total_obligation' => $amount,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now(),
        ]);

        \App\Models\DebtInstallment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(7),
            'amount_due' => $amount,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        // 3. Jurnal: Kas Dompet berkurang (Credit), Piutang bertambah (Debit)
        $desc = "Kas Dipinjam Teman: {$borrowerName} (Rp " . number_format($amount, 0, ',', '.') . ")";
        if (!empty($validated['notes'])) {
            $desc .= " - {$validated['notes']}";
        }

        $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $desc,
            transactionTime: now(),
            entries: [
                ['account_id' => $cashAccount->id, 'entry_type' => \App\Enums\EntryType::CREDIT, 'amount' => $amount],
                ['account_id' => $receivableAccount->id, 'entry_type' => \App\Enums\EntryType::DEBIT, 'amount' => $amount],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'friend_loan_given',
                'debt_id' => $debt->id,
                'borrower_name' => $borrowerName,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        \App\Models\AuditLog::create([
            'auditable_type' => Debt::class,
            'auditable_id' => $debt->id,
            'action' => 'lend_friend_cash',
            'old_values' => null,
            'new_values' => ['borrower_name' => $borrowerName, 'amount' => $amount],
            'reason' => "Kas tunai dipinjam teman di lapangan: {$borrowerName}",
        ]);

        return redirect()->back()->with('success', "Kas Rp " . number_format($amount, 0, ',', '.') . " berhasil dipinjamkan ke {$borrowerName} & otomatis tercatat sebagai Piutang Teman.");
    }

    /**
     * Rekonsiliasi Saldo Digital Malam Hari (Snapshot 4 Saldo Digital & Deteksi QRIS Nyusul)
     */
    public function reconcileDigital(Request $request, DigitalReconciliationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'work_day_id' => 'nullable|exists:work_days,id',
            'balances' => 'required|array',
            'balances.*.account_id' => 'required|exists:accounts,id',
            'balances.*.actual_balance' => 'required|integer|min:0',
        ]);

        $workDay = !empty($validated['work_day_id'])
            ? WorkDay::findOrFail($validated['work_day_id'])
            : (WorkDay::whereDate('work_date', now()->toDateString())->first() ?? WorkDay::latest('work_date')->first());

        if (!$workDay) {
            return redirect()->back()->with('error', 'Hari kerja aktif tidak ditemukan. Silakan buka hari kerja terlebih dahulu.');
        }

        $result = $service->reconcileDigitalAccounts($workDay, $validated['balances']);

        $message = "Rekonsiliasi saldo digital selesai. ";
        if ($result['adjusted_count'] > 0) {
            $parts = [];
            if ($result['total_surplus'] > 0) {
                $parts[] = "Surplus QRIS/Digital +Rp " . number_format($result['total_surplus'], 0, ',', '.');
            }
            if ($result['total_shortage'] > 0) {
                $parts[] = "Penyesuaian Biaya Admin/Kurang -Rp " . number_format($result['total_shortage'], 0, ',', '.');
            }
            $message .= implode(', ', $parts) . " berhasil dicatat ke pembukuan.";
        } else {
            $message .= "Semua saldo digital sudah sinkron dan cocok 100%.";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Tambah Target Belanja Survival / Wishlist Baru
     */
    public function storeGoal(Request $request, FinancialGoalService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:pangan,hygiene,work_essential,maintenance,wants',
            'priority_level' => 'required|integer|between:1,5',
            'target_amount' => 'required|integer|min:1',
            'target_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $goal = $service->createGoal($validated);

        return redirect()->back()->with('success', "Target belanja '{$goal->name}' (Rp " . number_format($goal->target_amount, 0, ',', '.') . ") berhasil disimpan ke daftar prioritas.");
    }

    /**
     * Alokasi Dana Bertahap ke Target Belanja
     */
    public function allocateGoalFunds(Request $request, int $id, FinancialGoalService $service): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $goal = FinancialGoal::findOrFail($id);
        $service->allocateFunds($goal, (int) $validated['amount'], $validated['notes'] ?? null);

        return redirect()->back()->with('success', "Dana Rp " . number_format($validated['amount'], 0, ',', '.') . " berhasil disisihkan ke target '{$goal->name}'. (Terkumpul: Rp " . number_format($goal->fresh()->allocated_amount, 0, ',', '.') . " / Rp " . number_format($goal->target_amount, 0, ',', '.') . ")");
    }

    /**
     * Realisasikan Belanja Target (Toko / Bengkel)
     */
    public function achieveGoal(Request $request, int $id, FinancialGoalService $service): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'actual_spent' => 'nullable|integer|min:1',
        ]);

        $goal = FinancialGoal::findOrFail($id);
        $account = Account::findOrFail($validated['account_id']);
        $spent = $validated['actual_spent'] ? (int) $validated['actual_spent'] : $goal->target_amount;

        $service->achieveGoal($goal, $account, $spent);

        return redirect()->back()->with('success', "Selamat! Target belanja '{$goal->name}' berhasil dibeli seharga Rp " . number_format($spent, 0, ',', '.') . " menggunakan {$account->name}.");
    }
}
