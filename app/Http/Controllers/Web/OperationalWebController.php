<?php

namespace App\Http\Controllers\Web;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActiveCodOrder;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\DriverSnapshot;
use App\Models\FinancialGoal;
use App\Models\Transaction;
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
use App\Services\ShopeeBonusCalculatorService;
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
        } else {
            // Salin otomatis pecahan dari closing/update fisik terakhir agar shift hari ini langsung punya baseline
            $latestDenom = \App\Models\CashDenomination::where('work_day_id', '!=', $workDay->id)
                ->where('total_physical_amount', '>', 0)
                ->latest('id')
                ->first();

            if ($latestDenom) {
                $denomRecord = \App\Models\CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
                $denomRecord->fill($latestDenom->only([
                    'bill_100000', 'bill_50000', 'bill_20000', 'bill_10000',
                    'bill_5000', 'bill_2000', 'bill_1000',
                    'coin_1000', 'coin_500', 'coin_200', 'coin_100',
                    'damaged_notes_amount', 'damaged_notes_note'
                ]));
                $denomRecord->total_physical_amount = $latestDenom->total_physical_amount;
                $denomRecord->save();
            } else {
                $cashAcc = Account::where('name', 'Cash')->orWhere('type', AccountType::CASH)->first();
                $savedDenoms = $cashAcc?->metadata['denominations'] ?? null;
                if (!empty($savedDenoms) && \App\Models\CashDenomination::calculateSum($savedDenoms) > 0) {
                    $denomRecord = \App\Models\CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
                    $denomRecord->fill($savedDenoms);
                    $denomRecord->total_physical_amount = \App\Models\CashDenomination::calculateSum($savedDenoms);
                    $denomRecord->save();
                }
            }
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

        $denomFields = [
            'bill_100000', 'bill_50000', 'bill_20000', 'bill_10000',
            'bill_5000', 'bill_2000', 'bill_1000',
            'coin_1000', 'coin_500', 'coin_200', 'coin_100'
        ];
        $denomData = $request->only($denomFields);
        $calculatedDenom = \App\Models\CashDenomination::calculateSum($denomData);
        $amount = $calculatedDenom > 0 ? $calculatedDenom : (int) ($validated['amount'] ?? 0);

        $account = Account::findOrFail($validated['account_id']);
        if (!$account->type->isAsset()) {
            return redirect()->back()->with('error', "Hanya akun aset / wadah simpanan kas (Bank, E-Wallet, Kas) yang dapat memiliki saldo awal.");
        }

        $passedDenoms = ($account->type === AccountType::CASH && $calculatedDenom > 0) ? $denomData : null;

        if ($account->initial_balance == 0) {
            $initialBalanceService->setInitialBalance($account, $amount, $passedDenoms);
        } else {
            $reason = $validated['reason'] ?? "Koreksi saldo awal akun {$account->name}";
            $initialBalanceService->correctInitialBalance($account, $amount, $reason, $passedDenoms);
        }

        // Jika akun bertipe Kas dan ada rincian pecahan fisik, simpan/update ke CashDenomination shift hari ini / aktif
        if ($account->type === AccountType::CASH && $calculatedDenom > 0) {
            $workDay = WorkDay::whereDate('work_date', now()->toDateString())->first()
                ?? WorkDay::latest('work_date')->first();

            if (!$workDay) {
                $workDay = WorkDay::create([
                    'work_date' => now()->toDateString(),
                    'day_type' => WorkDay::TYPE_DAY_OFF,
                    'status' => 'active',
                ]);
            }

            $denom = \App\Models\CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
            $denom->fill($denomData);
            $denom->total_physical_amount = $calculatedDenom;
            $denom->save();
        }

        return redirect()->back()->with('success', "Saldo awal akun {$account->name} berhasil diperbarui (Rp" . number_format($amount, 0, ',', '.') . ").");
    }

    /**
     * Update Fisik Uang Kas (Lembaran & Koin) Kapan Saja di Jalan (On-Demand)
     * Sinkron langsung ke Saldo Kas dan Checkpoint DriverSnapshot
     */
    public function updateCashDenominations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'work_day_id' => 'nullable|exists:work_days,id',
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
            'damaged_notes_amount' => 'nullable|integer|min:0',
            'damaged_notes_note' => 'nullable|string|max:255',
        ]);

        $workDay = !empty($validated['work_day_id'])
            ? WorkDay::findOrFail($validated['work_day_id'])
            : (WorkDay::whereDate('work_date', now()->toDateString())->first() ?? WorkDay::latest('work_date')->first());

        if (!$workDay) {
            $workDay = WorkDay::create([
                'work_date' => now()->toDateString(),
                'day_type' => WorkDay::TYPE_DAY_OFF,
                'status' => 'active',
            ]);
        }

        $denom = \App\Models\CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
        $oldValues = $denom->exists ? $denom->toArray() : null;

        $fields = [
            'bill_100000', 'bill_50000', 'bill_20000', 'bill_10000',
            'bill_5000', 'bill_2000', 'bill_1000',
            'coin_1000', 'coin_500', 'coin_200', 'coin_100',
        ];

        foreach ($fields as $f) {
            $denom->$f = (int) ($validated[$f] ?? 0);
        }
        $denom->damaged_notes_amount = (int) ($validated['damaged_notes_amount'] ?? 0);
        $denom->damaged_notes_note = $validated['damaged_notes_note'] ?? null;
        $totalPhysical = $denom->calculateTotal();
        $denom->total_physical_amount = $totalPhysical;
        $denom->save();

        // 1. Sinkronkan Saldo Akun Kas & metadata denominasi
        $cashAcc = Account::where('name', 'Cash')->orWhere('type', AccountType::CASH)->first();
        if ($cashAcc) {
            $meta = $cashAcc->metadata ?? [];
            $meta['denominations'] = $denom->toArray();
            $cashAcc->update([
                'current_balance' => $totalPhysical,
                'metadata' => $meta,
            ]);
        }

        // 2. Sinkronkan ke Checkpoint DriverSnapshot terbaru jika ada
        $latestSnapshot = \App\Models\DriverSnapshot::where('work_day_id', $workDay->id)
            ->latest('recorded_at')
            ->first();

        if ($latestSnapshot) {
            $latestSnapshot->update([
                'cash_balance' => $totalPhysical,
            ]);
        }

        \App\Models\AuditLog::create([
            'auditable_type' => \App\Models\CashDenomination::class,
            'auditable_id' => $denom->id,
            'action' => 'update_cash_denominations_ondemand',
            'old_values' => $oldValues,
            'new_values' => $denom->toArray(),
            'reason' => "Update fisik kas di saku/dompet on-demand: Rp " . number_format($totalPhysical, 0, ',', '.') . " ({$denom->total_pieces} lembar & koin)",
        ]);

        return redirect()->back()->with('success', "Kas fisik di dompet/saku berhasil diperbarui: Rp " . number_format($totalPhysical, 0, ',', '.') . " ({$denom->total_pieces} lembar & koin). Saldo kas dan data checkpoint driver langsung sinkron!");
    }

    /**
     * Terapkan Rincian Pecahan Transaksi Cash (Opsional) ke Denominasi Fisik Dompet Shift
     */
    protected function applyCashBreakdown(
        WorkDay $workDay,
        ?array $inflowBreakdown,
        ?array $outflowBreakdown,
        string $context = 'expense'
    ): void {
        $fields = [
            'bill_100000', 'bill_50000', 'bill_20000', 'bill_10000',
            'bill_5000', 'bill_2000', 'bill_1000',
            'coin_1000', 'coin_500', 'coin_200', 'coin_100',
        ];

        // Validasi apakah ada input pecahan yang > 0
        $hasInflow = false;
        if (is_array($inflowBreakdown)) {
            foreach ($fields as $f) {
                if (!empty($inflowBreakdown[$f]) && (int) $inflowBreakdown[$f] > 0) {
                    $hasInflow = true;
                    break;
                }
            }
        }

        $hasOutflow = false;
        if (is_array($outflowBreakdown)) {
            foreach ($fields as $f) {
                if (!empty($outflowBreakdown[$f]) && (int) $outflowBreakdown[$f] > 0) {
                    $hasOutflow = true;
                    break;
                }
            }
        }

        if (!$hasInflow && !$hasOutflow) {
            return; // 100% Opsional: Tidak ada rincian pecahan yang diisi, abaikan dengan aman
        }

        // Ambil atau buat record denominasi kas shift ini
        $denom = \App\Models\CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
        if (!$denom->exists) {
            // Ambil closing fisik terakhir sebagai baseline awal jika belum ada
            $lastDenom = \App\Models\CashDenomination::where('work_day_id', '!=', $workDay->id)->latest()->first();
            if ($lastDenom) {
                foreach ($fields as $f) {
                    $denom->$f = (int) $lastDenom->$f;
                }
            }
        }

        // Context:
        // 'expense'  : $inflowBreakdown = $paid (uang driver KELUAR), $outflowBreakdown = $change (kembalian MASUK)
        // 'cod_cash' : $inflowBreakdown = $customerGave (uang customer MASUK), $outflowBreakdown = $changeReturned (kembalian KELUAR)
        // 'income'   : $inflowBreakdown = $tip (uang tip MASUK), $outflowBreakdown = null
        if ($context === 'expense') {
            if (is_array($inflowBreakdown)) {
                foreach ($fields as $f) {
                    $qty = (int) ($inflowBreakdown[$f] ?? 0);
                    if ($qty > 0) {
                        $denom->$f = max(0, (int) $denom->$f - $qty);
                    }
                }
            }
            if (is_array($outflowBreakdown)) {
                foreach ($fields as $f) {
                    $qty = (int) ($outflowBreakdown[$f] ?? 0);
                    if ($qty > 0) {
                        $denom->$f = (int) $denom->$f + $qty;
                    }
                }
            }
        } elseif ($context === 'cod_cash') {
            if (is_array($inflowBreakdown)) {
                foreach ($fields as $f) {
                    $qty = (int) ($inflowBreakdown[$f] ?? 0);
                    if ($qty > 0) {
                        $denom->$f = (int) $denom->$f + $qty;
                    }
                }
            }
            if (is_array($outflowBreakdown)) {
                foreach ($fields as $f) {
                    $qty = (int) ($outflowBreakdown[$f] ?? 0);
                    if ($qty > 0) {
                        $denom->$f = max(0, (int) $denom->$f - $qty);
                    }
                }
            }
        } elseif ($context === 'income') {
            if (is_array($inflowBreakdown)) {
                foreach ($fields as $f) {
                    $qty = (int) ($inflowBreakdown[$f] ?? 0);
                    if ($qty > 0) {
                        $denom->$f = (int) $denom->$f + $qty;
                    }
                }
            }
        }

        $denom->total_physical_amount = $denom->calculateTotal();
        $denom->save();

        // Sinkronkan checkpoint DriverSnapshot jika ada
        $latestSnapshot = $workDay->latestSnapshot;
        if ($latestSnapshot) {
            $cashAcc = Account::where('name', 'Cash')->first();
            $latestSnapshot->update([
                'cash_balance' => $cashAcc ? (int) $cashAcc->current_balance : (int) $denom->total_physical_amount,
            ]);
        }
    }

    /**
     * Catat Beli Bensin
     */
    public function storeFuel(
        Request $request,
        QuickFuelService $fuelService,
        LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'source_account_id' => 'required|exists:accounts,id',
            'vendor_type' => 'required|in:spbu,madura',
            'amount' => 'required|integer|min:1000',
            'cashback_amount' => 'nullable|integer|min:0',
            'paid_breakdown' => 'nullable|array',
            'change_breakdown' => 'nullable|array',
        ]);

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $account = Account::findOrFail($validated['source_account_id']);

        $tx = $fuelService->recordFuel(
            workDay: $workDay,
            sourceAccount: $account,
            vendorType: $validated['vendor_type'],
            amount: (int) $validated['amount']
        );

        $cashback = (int) ($validated['cashback_amount'] ?? 0);
        $cashbackTx = $this->recordShopeePayCashback(
            $ledgerService,
            $workDay,
            $account,
            $cashback,
            $tx->description
        );

        // Penerapan rincian pecahan kas jika sumber pembayaran adalah Kas Tunai
        $isCash = ($account->name === 'Cash' || $account->type === AccountType::CASH);
        $paidBreakdown = $request->input('paid_breakdown');
        $changeBreakdown = $request->input('change_breakdown');
        if ($isCash && ($paidBreakdown || $changeBreakdown)) {
            $this->applyCashBreakdown($workDay, $paidBreakdown, $changeBreakdown, 'expense');
            $tx->update([
                'metadata' => array_merge($tx->metadata ?? [], [
                    'cash_breakdown' => [
                        'paid' => $paidBreakdown,
                        'change' => $changeBreakdown,
                    ],
                ]),
            ]);
        }

        $msg = 'Transaksi pembelian bensin berhasil dicatat.';
        if ($cashbackTx) {
            $msg .= ' Mendapatkan cashback ShopeePay Rp ' . number_format($cashback, 0, ',', '.') . ' (saldo ShopeePay bertambah kembali).';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Catat Parkir Kendaraan (Motor / Mobil, On-Bid / Off-Bid, Mandiri vs Resto)
     */
    public function storeParking(
        Request $request,
        QuickParkingService $parkingService,
        LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'nullable|exists:work_days,id',
            'amount' => 'required|integer|min:0',
            'restaurant_name' => 'nullable|string|max:255',
            'order_code' => 'nullable|string|max:50',
            'is_covered_by_resto' => 'nullable|boolean',
            'source_account_id' => 'nullable|exists:accounts,id',
            'vehicle_type' => 'nullable|string|in:motor,mobil,lainnya',
            'cashback_amount' => 'nullable|integer|min:0',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'paid_breakdown' => 'nullable|array',
            'change_breakdown' => 'nullable|array',
        ]);

        $workDay = !empty($validated['work_day_id'])
            ? WorkDay::findOrFail($validated['work_day_id'])
            : (WorkDay::whereDate('work_date', now()->toDateString())->first() 
                ?? WorkDay::create([
                    'work_date' => now()->toDateString(),
                    'day_type' => WorkDay::TYPE_DAY_OFF,
                    'status' => 'active',
                ]));

        $isCovered = $request->boolean('is_covered_by_resto');
        $vehicleType = $validated['vehicle_type'] ?? 'motor';
        $sourceAccountId = !empty($validated['source_account_id']) ? (int) $validated['source_account_id'] : null;

        $rawResto = trim($validated['restaurant_name'] ?? '');
        $orderCode = trim($validated['order_code'] ?? '');
        $restaurantName = $rawResto;
        if (!empty($orderCode)) {
            $restaurantName = $rawResto ? "{$rawResto} #{$orderCode}" : "Order #{$orderCode}";
        }

        $parkingTx = $parkingService->recordParking(
            workDay: $workDay,
            amount: (int) $validated['amount'],
            restaurantName: $restaurantName ?: null,
            latitude: !empty($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: !empty($validated['longitude']) ? (float) $validated['longitude'] : null,
            isCoveredByResto: $isCovered,
            sourceAccountId: $sourceAccountId,
            vehicleType: $vehicleType
        );

        $cashback = (int) ($validated['cashback_amount'] ?? 0);
        $cashbackTx = null;
        if (!$isCovered && $sourceAccountId && $cashback > 0) {
            $sourceAcc = Account::find($sourceAccountId);
            if ($sourceAcc) {
                $cashbackTx = $this->recordShopeePayCashback(
                    $ledgerService,
                    $workDay,
                    $sourceAcc,
                    $cashback,
                    $parkingTx->description
                );
            }
        }

        // Terapkan rincian pecahan kas jika parkir tidak gratis dan sumber pembayaran adalah Kas
        if (!$isCovered) {
            $sourceAcc = $sourceAccountId ? Account::find($sourceAccountId) : Account::where('name', 'Cash')->first();
            $isCash = $sourceAcc ? ($sourceAcc->name === 'Cash' || $sourceAcc->type === AccountType::CASH) : true;
            $paidBreakdown = $request->input('paid_breakdown');
            $changeBreakdown = $request->input('change_breakdown');

            if ($isCash && ($paidBreakdown || $changeBreakdown)) {
                $this->applyCashBreakdown($workDay, $paidBreakdown, $changeBreakdown, 'expense');
                $parkingTx->update([
                    'metadata' => array_merge($parkingTx->metadata ?? [], [
                        'cash_breakdown' => [
                            'paid' => $paidBreakdown,
                            'change' => $changeBreakdown,
                        ],
                    ]),
                ]);
            }
        }

        $vehicleLabel = $vehicleType === 'mobil' ? 'Mobil' : 'Motor';
        $msg = $isCovered 
            ? "Parkir {$vehicleLabel} gratis (ditanggung resto) berhasil dicatat tanpa memotong saldo." 
            : "Parkir {$vehicleLabel} berhasil dicatat (Rp" . number_format($validated['amount'], 0, ',', '.') . ").";

        if ($cashbackTx) {
            $msg .= ' Mendapatkan cashback ShopeePay Rp ' . number_format($cashback, 0, ',', '.') . ' (saldo ShopeePay bertambah kembali).';
        }

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
            'customer_cash_breakdown' => 'nullable|array',
            'change_cash_breakdown' => 'nullable|array',
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

        // Jika bayar tunai (cash) dan ada rincian pecahan lembaran/koin yang diisi opsional
        if ($completed->payment_method === 'cash') {
            $customerBreakdown = $request->input('customer_cash_breakdown');
            $changeBreakdown = $request->input('change_cash_breakdown');

            if ($customerBreakdown || $changeBreakdown) {
                $this->applyCashBreakdown($order->workDay, $customerBreakdown, $changeBreakdown, 'cod_cash');
            }
        }

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
            'source_account_id' => 'required',
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'tenor_months' => 'nullable|integer|min:1|max:24',
            'cashback_amount' => 'nullable|integer|min:0',
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

        $tx = $ledgerService->recordTransaction(
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

        $cashback = (int) ($validated['cashback_amount'] ?? 0);
        $cashbackTx = $this->recordShopeePayCashback(
            $ledgerService,
            $workDay,
            $source,
            $cashback,
            $validated['description'],
            $tx->transaction_time
        );

        $msg = "Pengeluaran {$validated['description']} berhasil dicatat.";
        if ($cashbackTx) {
            $msg .= " Mendapatkan cashback ShopeePay Rp " . number_format($cashback, 0, ',', '.') . " (saldo ShopeePay bertambah kembali).";
        }

        return redirect()->back()->with('success', $msg);
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
            'cashback_amount' => 'nullable|integer|min:0',
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

        $tx = $ledgerService->recordTransaction(
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

        $cashback = (int) ($validated['cashback_amount'] ?? 0);
        $cashbackTx = $this->recordShopeePayCashback(
            $ledgerService,
            $workDay,
            $sourceAccount,
            $cashback,
            $validated['description'],
            $tx->transaction_time
        );

        $msg = "Biaya serba-serbi '{$validated['description']}' (Rp " . number_format($amount, 0, ',', '.') . ") berhasil dicatat memotong {$sourceAccount->name}.";
        if ($cashbackTx) {
            $msg .= " Mendapatkan cashback ShopeePay Rp " . number_format($cashback, 0, ',', '.') . " (saldo ShopeePay bertambah kembali).";
        }

        return redirect()->back()->with('success', $msg);
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
            'total_obligation' => 'required|integer|min:1',
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
    public function paySubscription(
        Request $request,
        \App\Services\SubscriptionService $subService,
        LedgerService $ledgerService
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'required|exists:work_days,id',
            'subscription_id' => 'required|exists:subscriptions,id',
            'payment_account_id' => 'required|exists:accounts,id',
            'actual_amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'cashback_amount' => 'nullable|integer|min:0',
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

        $cashback = (int) ($validated['cashback_amount'] ?? 0);
        $cashbackTx = $this->recordShopeePayCashback(
            $ledgerService,
            $workDay,
            $paymentAccount,
            $cashback,
            "Tagihan Langganan {$subscription->name}"
        );

        $msg = "Tagihan rutin '{$subscription->name}' berhasil dibayar menggunakan {$paymentAccount->name}!";
        if ($cashbackTx) {
            $msg .= " Mendapatkan cashback ShopeePay Rp " . number_format($cashback, 0, ',', '.') . " (saldo ShopeePay bertambah kembali).";
        }

        return redirect()->back()->with('success', $msg);
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
     * Rekonsiliasi & Checkpoint Snapshot Saldo di Jalan (ShopeeFood vs Wadah Dompet & Bank)
     */
    public function reconcileDigital(Request $request, DigitalReconciliationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'work_day_id' => 'nullable|exists:work_days,id',
            'is_custom_time' => 'nullable',
            'custom_recorded_at' => 'nullable|string',
            'auto_recorded_at' => 'nullable|string',
            'recorded_at' => 'nullable|string',
            'completed_orders' => 'nullable|integer|min:0',
            'delivery_fee' => 'nullable|integer|min:0',
            'hub_income' => 'nullable|integer|min:0',
            'hub_bonus' => 'nullable|integer|min:0',
            'customer_tips' => 'nullable|integer|min:0',
            'cod_qris_amount' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
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

        // Tentukan waktu checkpoint (Otomatis sekarang vs Custom jika telat)
        $isCustomTime = $request->boolean('is_custom_time') || !empty($validated['is_custom_time']);
        if ($isCustomTime && !empty($validated['custom_recorded_at'])) {
            try {
                $recordedAt = Carbon::parse($validated['custom_recorded_at'], 'Asia/Jakarta');
            } catch (\Exception $e) {
                return redirect()->back()->withInput()->with('error', 'Format tanggal dan jam custom tidak valid.');
            }
        } elseif (!empty($validated['recorded_at'])) {
            $recordedAt = Carbon::parse($validated['recorded_at'], 'Asia/Jakarta');
        } else {
            $recordedAt = now();
        }

        // Validasi Ketat: Waktu checkpoint TIDAK BOLEH mendahului atau sama dengan checkpoint sebelumnya di shift ini
        $latestPreviousSnapshot = \App\Models\DriverSnapshot::where('work_day_id', $workDay->id)
            ->latest('recorded_at')
            ->first();

        if ($latestPreviousSnapshot && $recordedAt->lessThanOrEqualTo($latestPreviousSnapshot->recorded_at)) {
            $prevTimeStr = $latestPreviousSnapshot->recorded_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s');
            $attemptedTimeStr = $recordedAt->timezone('Asia/Jakarta')->format('d M Y, H:i:s');
            return redirect()->back()
                ->withInput()
                ->with('error', "Waktu checkpoint ({$attemptedTimeStr} WIB) tidak boleh mendahului atau sama dengan checkpoint sebelumnya ({$prevTimeStr} WIB). Waktu harus lebih baru!");
        }

        if ($recordedAt->isAfter(now()->addMinutes(2))) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Waktu checkpoint tidak boleh melebihi waktu sekarang (masa depan)!");
        }

        $codQrisAmount = (int) ($validated['cod_qris_amount'] ?? 0);

        // 1. Eksekusi rekonsiliasi saldo akun melalui DigitalReconciliationService (memisahkan tagihan COD & tip murni)
        $result = $service->reconcileDigitalAccounts($workDay, $validated['balances'], $codQrisAmount);

        // 2. Simpan Checkpoint DriverSnapshot
        $shopeepayBalance = 0;
        $gopayBalance = 0;
        $gopayMerchantBalance = 0;

        foreach ($validated['balances'] as $b) {
            $acc = Account::find($b['account_id']);
            if (!$acc) continue;
            $val = (int) $b['actual_balance'];
            if ($acc->name === 'ShopeePay') {
                $shopeepayBalance = $val;
            } elseif ($acc->name === 'GoPay') {
                $gopayBalance = $val;
            } elseif ($acc->name === 'GoPay Merchant') {
                $gopayMerchantBalance = $val;
            }
        }

        // Kas dompet otomatis dihitung dari catatan transaksi pembukuan sistem terkini (tidak perlu dihitung manual di jalan)
        $cashAcc = Account::where('name', 'Cash')->first();
        $cashBalance = $cashAcc ? (int) $cashAcc->current_balance : 0;

        $deliveryFee = (int) ($validated['delivery_fee'] ?? 0);
        $hubIncome = (int) ($validated['hub_income'] ?? 0);
        $hubBonus = (int) ($validated['hub_bonus'] ?? 0);
        $customerTips = (int) ($validated['customer_tips'] ?? 0);
        $completedOrders = (int) ($validated['completed_orders'] ?? 0);

        \App\Models\DriverSnapshot::create([
            'work_day_id' => $workDay->id,
            'recorded_at' => $recordedAt,
            'completed_orders' => $completedOrders,
            'delivery_fee' => $deliveryFee,
            'hub_income' => $hubIncome,
            'hub_bonus' => $hubBonus,
            'customer_tips' => $customerTips,
            'shopeepay_balance' => $shopeepayBalance,
            'gopay_balance' => $gopayBalance,
            'gopay_merchant_balance' => $gopayMerchantBalance,
            'cash_balance' => $cashBalance,
            'notes' => $validated['notes'] ?? null,
        ]);

        $totalShopee = $deliveryFee + $hubIncome + $hubBonus + $customerTips;
        $totalWallets = $shopeepayBalance + $cashBalance + $gopayBalance + $gopayMerchantBalance;
        $timeStr = $recordedAt->timezone('Asia/Jakarta')->format('H:i:s');

        $message = "Checkpoint jam {$timeStr} WIB tersimpan! (ShopeeFood: Rp " . number_format($totalShopee, 0, ',', '.') . " vs Posisi Wadah: Rp " . number_format($totalWallets, 0, ',', '.') . "). ";

        if ($result['adjusted_count'] > 0) {
            $parts = [];
            if (!empty($result['cod_reimbursed']) && $result['cod_reimbursed'] > 0) {
                $parts[] = "Modal COD via QRIS Rp " . number_format($result['cod_reimbursed'], 0, ',', '.') . " dialokasikan ke Talangan";
            }
            if (!empty($result['net_qris_tip']) && $result['net_qris_tip'] > 0) {
                $parts[] = "Tip QRIS Murni +Rp " . number_format($result['net_qris_tip'], 0, ',', '.');
            } elseif ($result['total_surplus'] > 0 && empty($result['cod_reimbursed'])) {
                $parts[] = "Surplus/Tip Terdeteksi +Rp " . number_format($result['total_surplus'], 0, ',', '.');
            }
            if ($result['total_shortage'] > 0) {
                $parts[] = "Penyesuaian Biaya/Selisih -Rp " . number_format($result['total_shortage'], 0, ',', '.');
            }
            $message .= implode(', ', $parts) . " telah diselaraskan ke pembukuan.";
        } else {
            $message .= "Semua saldo wadah cocok 100%.";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Catat Tip Langsung Pelanggan (Ikhlas / Sukarela di Lapangan via Cash / QRIS / Transfer)
     */
    public function storeQuickTip(Request $request, LedgerService $ledgerService): RedirectResponse
    {
        $validated = $request->validate([
            'work_day_id' => 'nullable|exists:work_days,id',
            'amount' => 'required|integer|min:1000',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string|max:255',
            'tip_cash_breakdown' => 'nullable|array',
        ]);

        $workDay = !empty($validated['work_day_id'])
            ? WorkDay::findOrFail($validated['work_day_id'])
            : (WorkDay::whereDate('work_date', now()->toDateString())->first() ?? WorkDay::latest('work_date')->first());

        if (!$workDay) {
            return redirect()->back()->with('error', 'Hari kerja aktif tidak ditemukan. Silakan buka hari kerja terlebih dahulu.');
        }

        $account = Account::findOrFail($validated['account_id']);
        $amount = (int) $validated['amount'];
        $notes = $validated['notes'] ?? null;

        $isCash = ($account->name === 'Cash' || $account->type === AccountType::CASH);
        $method = $isCash ? 'cash' : ($account->name === 'GoPay Merchant' ? 'qris' : strtolower($account->name));

        $tipIncomeAccount = $isCash
            ? Account::firstOrCreate(
                ['name' => 'Tip Cash / Surplus COD'],
                ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
            )
            : Account::firstOrCreate(
                ['name' => 'Pendapatan QRIS / Tip Non-Tunai'],
                ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
            );

        $desc = "Tip Langsung Pelanggan via {$account->name}: Rp " . number_format($amount, 0, ',', '.') . ($notes ? " ({$notes})" : "");

        $tx = $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'income',
            description: $desc,
            transactionTime: now(),
            entries: [
                ['account_id' => $account->id, 'entry_type' => EntryType::DEBIT, 'amount' => $amount],
                ['account_id' => $tipIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $amount],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'direct_customer_tip',
                'tip_amount' => $amount,
                'payment_method' => $method,
                'account_id' => $account->id,
                'account_name' => $account->name,
                'notes' => $notes,
            ]
        );

        // Jika tip cash dan ada rincian pecahan
        $tipBreakdown = $request->input('tip_cash_breakdown');
        if ($isCash && $tipBreakdown) {
            $this->applyCashBreakdown($workDay, $tipBreakdown, null, 'income');
            $tx->update([
                'metadata' => array_merge($tx->metadata ?? [], [
                    'cash_breakdown' => [
                        'received' => $tipBreakdown,
                    ],
                ]),
            ]);
        }

        $msg = "Alhamdulillah! Tip Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat masuk ke {$account->name}.";
        if ($account->name === 'GoPay Merchant') {
            $msg .= " (Dana di GoPay Merchant otomatis dicairkan ke GoPay reguler setiap 1 jam jika saldo ≥ Rp10.000).";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Update Bonus Hub ShopeeFood (Jam 20:00+ Cut-off, Total Pesanan & Validasi Online >= 9 Jam)
     */
    public function updateHubBonus(
        Request $request,
        ShopeeBonusCalculatorService $calculator
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'nullable|exists:work_days,id',
            'completed_orders' => 'required|integer|min:0',
            'is_online_9_hours' => 'nullable',
        ]);

        $workDay = !empty($validated['work_day_id'])
            ? WorkDay::findOrFail($validated['work_day_id'])
            : (WorkDay::whereDate('work_date', now()->toDateString())->first() ?? WorkDay::latest('work_date')->first());

        if (!$workDay) {
            return redirect()->back()->with('error', 'Hari kerja aktif tidak ditemukan.');
        }

        $completedOrders = (int) $validated['completed_orders'];
        $isOnline9Hours = filter_var($request->input('is_online_9_hours', false), FILTER_VALIDATE_BOOLEAN);

        // Rumus bertingkat jika online >= 9 jam, 0 jika < 9 jam
        $calculatedBonus = $isOnline9Hours ? $calculator->calculateBonus($completedOrders) : 0;

        // Update WorkDay
        $workDay->total_orders = $completedOrders;
        $workDay->is_performance_eligible = $isOnline9Hours;
        if ($isOnline9Hours) {
            $workDay->online_hours = max(9.0, (float) ($workDay->online_hours ?? 9.0));
        }
        $workDay->save();

        // Update checkpoint / DriverSnapshot terbaru
        $latestSnapshot = $workDay->latestSnapshot;
        if ($latestSnapshot) {
            $latestSnapshot->update([
                'hub_bonus' => $calculatedBonus,
                'completed_orders' => max($latestSnapshot->completed_orders, $completedOrders),
            ]);
        } else {
            $cashAcc = Account::where('name', 'Cash')->first();
            $spayAcc = Account::where('name', 'ShopeePay')->first();
            $gopayAcc = Account::where('name', 'GoPay')->first();
            $merchantAcc = Account::where('name', 'GoPay Merchant')->first();

            DriverSnapshot::create([
                'work_day_id' => $workDay->id,
                'recorded_at' => now(),
                'completed_orders' => $completedOrders,
                'delivery_fee' => 0,
                'hub_income' => 0,
                'hub_bonus' => $calculatedBonus,
                'customer_tips' => 0,
                'shopeepay_balance' => $spayAcc?->current_balance ?? 0,
                'gopay_balance' => $gopayAcc?->current_balance ?? 0,
                'gopay_merchant_balance' => $merchantAcc?->current_balance ?? 0,
                'cash_balance' => $cashAcc?->current_balance ?? 0,
                'notes' => 'Update Bonus Hub Jam 20:00+',
            ]);
        }

        if ($isOnline9Hours) {
            $msg = "Bonus Hub berhasil dihitung: Rp " . number_format($calculatedBonus, 0, ',', '.') . " ({$completedOrders} pesanan, status online ≥ 9 jam: TERPENUHI). Data insentif di checkpoint terbaru telah diperbarui!";
            if ($calculatedBonus > 0 && (now()->hour >= 23 || now()->hour < 5)) {
                $msg .= " Jam 23:00 telah tiba, Anda dapat langsung mengonfirmasi pencairan ke ShopeePay.";
            }
        } else {
            $msg = "Status waktu online narik tercatat < 9 jam. Sesuai regulasi ShopeeFood, bonus insentif hari ini adalah Rp 0. Data checkpoint telah disesuaikan.";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Konfirmasi Pencairan Bonus Hub ShopeeFood Langsung Masuk ke ShopeePay (Pukul 23:00 WIB)
     */
    public function claimHubBonus(
        Request $request,
        LedgerService $ledgerService,
        ShopeeBonusCalculatorService $calculator
    ): RedirectResponse {
        $validated = $request->validate([
            'work_day_id' => 'nullable|exists:work_days,id',
        ]);

        $workDay = !empty($validated['work_day_id'])
            ? WorkDay::findOrFail($validated['work_day_id'])
            : (WorkDay::whereDate('work_date', now()->toDateString())->first() ?? WorkDay::latest('work_date')->first());

        if (!$workDay) {
            return redirect()->back()->with('error', 'Hari kerja aktif tidak ditemukan.');
        }

        // Cek apakah bonus hari ini sudah pernah dicairkan
        $existingClaim = Transaction::where('work_day_id', $workDay->id)
            ->where(function ($q) {
                $q->whereJsonContains('metadata->type', 'hub_bonus_payout')
                  ->orWhereJsonContains('metadata->type', 'shopeepay_bonus_disbursement');
            })->first();

        if ($existingClaim) {
            return redirect()->back()->with('error', 'Bonus Hub untuk hari ini sudah pernah dikonfirmasi dan dicairkan ke ShopeePay.');
        }

        $latestSnapshot = $workDay->latestSnapshot;
        $bonusAmount = (int) ($latestSnapshot?->hub_bonus ?? 0);

        if ($bonusAmount <= 0 && $workDay->is_performance_eligible && $workDay->total_orders > 15) {
            $bonusAmount = $calculator->calculateBonus((int) $workDay->total_orders);
        }

        if ($bonusAmount <= 0) {
            return redirect()->back()->with('error', 'Tidak ada nominal bonus hub yang eligible untuk dicairkan (Wajib online ≥ 9 jam dan pesanan eligible > 15).');
        }

        $shopeePay = Account::firstOrCreate(
            ['name' => 'ShopeePay'],
            ['type' => AccountType::EWALLET, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
        );

        $bonusIncomeAcc = Account::firstOrCreate(
            ['name' => 'Bonus Hub ShopeeFood'],
            ['type' => AccountType::INCOME, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
        );

        $desc = "Pencairan Resmi Bonus Insentif Hub ke ShopeePay: Rp " . number_format($bonusAmount, 0, ',', '.');

        $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'income',
            description: $desc,
            transactionTime: now(),
            entries: [
                ['account_id' => $shopeePay->id, 'entry_type' => EntryType::DEBIT, 'amount' => $bonusAmount],
                ['account_id' => $bonusIncomeAcc->id, 'entry_type' => EntryType::CREDIT, 'amount' => $bonusAmount],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'hub_bonus_payout',
                'amount' => $bonusAmount,
                'work_day_id' => $workDay->id,
                'orders_count' => $workDay->total_orders,
                'online_hours_eligible' => true,
            ]
        );

        if ($latestSnapshot) {
            $latestSnapshot->update([
                'hub_bonus' => $bonusAmount,
                'shopeepay_balance' => $shopeePay->fresh()->current_balance,
            ]);
        }

        return redirect()->back()->with('success', "Alhamdulillah! Bonus Hub Rp " . number_format($bonusAmount, 0, ',', '.') . " berhasil dikonfirmasi dan langsung masuk ke saldo ShopeePay Anda (Saldo ShopeePay baru: Rp " . number_format($shopeePay->fresh()->current_balance, 0, ',', '.') . ").");
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

    /**
     * Kurangi Saldo Wadah (Biaya Admin Debit, Bunga, Biaya Pemeliharaan, dll)
     */
    public function deductAccountBalance(Request $request, LedgerService $ledgerService): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'transaction_time' => 'nullable|date',
            'work_day_id' => 'nullable|exists:work_days,id',
            'save_as_recurring' => 'nullable|boolean',
            'deduction_day' => 'nullable|integer|between:1,31',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        if (!$account->type->isAsset()) {
            return redirect()->back()->with('error', "Hanya wadah simpanan kas/bank/e-wallet yang dapat dikurangi saldonya.");
        }

        $workDay = !empty($validated['work_day_id'])
            ? WorkDay::findOrFail($validated['work_day_id'])
            : (WorkDay::whereDate('work_date', now()->toDateString())->first()
                ?? WorkDay::create([
                    'work_date' => now()->toDateString(),
                    'day_type' => WorkDay::TYPE_DAY_OFF,
                    'status' => 'active',
                ]));

        $expenseAccount = Account::firstOrCreate(
            ['name' => 'Beban Biaya Admin & Pemeliharaan Wadah'],
            ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
        );

        $txTime = !empty($validated['transaction_time']) ? Carbon::parse($validated['transaction_time']) : now();
        $amount = (int) $validated['amount'];
        $desc = trim($validated['description']);

        $tx = $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: "[Biaya Wadah] {$account->name}: {$desc}",
            transactionTime: $txTime,
            entries: [
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $amount],
                ['account_id' => $account->id, 'entry_type' => EntryType::CREDIT, 'amount' => $amount],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'account_admin_deduction',
                'account_id' => $account->id,
                'deduction_reason' => $desc,
                'amount' => $amount,
            ]
        );

        \App\Models\AuditLog::create([
            'auditable_type' => Account::class,
            'auditable_id' => $account->id,
            'action' => 'account_deduction',
            'old_values' => ['balance' => $account->current_balance + $amount],
            'new_values' => ['balance' => $account->fresh()->current_balance],
            'reason' => "Pengurangan saldo wadah {$account->name} sebesar Rp " . number_format($amount, 0, ',', '.') . " ({$desc}).",
        ]);

        if (!empty($validated['save_as_recurring'])) {
            \App\Models\AccountDeduction::create([
                'account_id' => $account->id,
                'name' => $desc,
                'amount' => $amount,
                'deduction_day' => (int) ($validated['deduction_day'] ?? now()->day),
                'last_deducted_at' => now(),
                'is_active' => true,
            ]);
        }

        return redirect()->back()->with('success', "Saldo wadah {$account->name} berhasil dipotong Rp " . number_format($amount, 0, ',', '.') . " untuk '{$desc}'.");
    }

    /**
     * Tambah / Atur Jadwal Potongan Rutin Bulanan untuk Wadah Tertentu
     */
    public function storeAccountDeduction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'name' => 'required|string|max:100',
            'amount' => 'required|integer|min:1',
            'deduction_day' => 'required|integer|between:1,31',
            'notes' => 'nullable|string|max:255',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        if (!$account->type->isAsset()) {
            return redirect()->back()->with('error', "Jadwal potongan rutin hanya dapat diatur untuk wadah kas/bank/e-wallet.");
        }

        \App\Models\AccountDeduction::create([
            'account_id' => $account->id,
            'name' => $validated['name'],
            'amount' => (int) $validated['amount'],
            'deduction_day' => (int) $validated['deduction_day'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', "Jadwal potongan bulanan '{$validated['name']}' (Rp " . number_format($validated['amount'], 0, ',', '.') . " / Tgl {$validated['deduction_day']}) berhasil disimpan untuk {$account->name}.");
    }

    /**
     * Hapus Jadwal Potongan Rutin Wadah
     */
    public function destroyAccountDeduction(int $id): RedirectResponse
    {
        $deduction = \App\Models\AccountDeduction::findOrFail($id);
        $name = $deduction->name;
        $accName = $deduction->account->name;
        $deduction->delete();

        return redirect()->back()->with('success', "Aturan potongan rutin '{$name}' pada {$accName} berhasil dihapus.");
    }

    /**
     * Eksekusi Cepat Potongan Rutin Bulanan Wadah Saat Tanggal / Waktunya Tiba
     */
    public function executeAccountDeduction(int $id, LedgerService $ledgerService): RedirectResponse
    {
        $deduction = \App\Models\AccountDeduction::with('account')->findOrFail($id);
        $account = $deduction->account;
        $oldBalance = $account->current_balance;

        $workDay = WorkDay::whereDate('work_date', now()->toDateString())->first()
            ?? WorkDay::create([
                'work_date' => now()->toDateString(),
                'day_type' => WorkDay::TYPE_DAY_OFF,
                'status' => 'active',
            ]);

        $expenseAccount = Account::firstOrCreate(
            ['name' => 'Beban Biaya Admin & Pemeliharaan Wadah'],
            ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
        );

        $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: "[Potongan Rutin Wadah] {$account->name}: {$deduction->name}",
            transactionTime: now(),
            entries: [
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $deduction->amount],
                ['account_id' => $account->id, 'entry_type' => EntryType::CREDIT, 'amount' => $deduction->amount],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'account_admin_deduction',
                'account_id' => $account->id,
                'deduction_id' => $deduction->id,
                'deduction_name' => $deduction->name,
                'amount' => $deduction->amount,
            ]
        );

        $deduction->update(['last_deducted_at' => now()]);

        \App\Models\AuditLog::create([
            'auditable_type' => Account::class,
            'auditable_id' => $account->id,
            'action' => 'account_deduction_executed',
            'old_values' => ['balance' => $oldBalance],
            'new_values' => ['balance' => $account->fresh()->current_balance],
            'reason' => "Eksekusi potongan rutin bulanan {$deduction->name} pada wadah {$account->name} sebesar Rp " . number_format($deduction->amount, 0, ',', '.'),
        ]);

        return redirect()->back()->with('success', "Potongan rutin '{$deduction->name}' (Rp " . number_format($deduction->amount, 0, ',', '.') . ") berhasil dipotong dari {$account->name}.");
    }

    /**
     * Proses pencatatan reward cashback ShopeePay jika ada.
     */
    protected function recordShopeePayCashback(
        LedgerService $ledgerService,
        WorkDay $workDay,
        Account $sourceAccount,
        int $cashbackAmount,
        string $referenceDesc,
        ?Carbon $transactionTime = null
    ): ?Transaction {
        if ($cashbackAmount <= 0) {
            return null;
        }

        // Pastikan hanya diproses jika akun sumber adalah ShopeePay
        if (!str_contains(strtolower($sourceAccount->name), 'shopeepay')) {
            return null;
        }

        $cashbackIncomeAccount = Account::firstOrCreate(
            ['name' => 'Pendapatan Cashback & Reward ShopeePay'],
            [
                'type' => AccountType::INCOME,
                'current_balance' => 0,
                'initial_balance' => 0,
                'is_active' => true,
            ]
        );

        $txTime = $transactionTime ?? now();

        return $ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'income',
            description: "[Cashback ShopeePay] Reward atas {$referenceDesc}",
            transactionTime: $txTime,
            entries: [
                ['account_id' => $sourceAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $cashbackAmount],
                ['account_id' => $cashbackIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $cashbackAmount],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'shopeepay_cashback',
                'source_account_id' => $sourceAccount->id,
                'reference' => $referenceDesc,
                'cashback_amount' => $cashbackAmount,
            ]
        );
    }

    /**
     * Edit Keterangan Transaksi (Koreksi Salah Ketik / Typo)
     */
    public function updateTransactionDescription(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'transaction_time' => 'nullable|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction = Transaction::findOrFail($id);
        $oldDescription = $transaction->description;
        $newDescription = trim($validated['description']);

        $oldTime = $transaction->transaction_time;
        $newTime = !empty($validated['transaction_time'])
            ? Carbon::parse($validated['transaction_time'])
            : $oldTime;

        $descChanged = ($oldDescription !== $newDescription);
        $timeChanged = ($newTime && $oldTime->format('Y-m-d H:i') !== $newTime->format('Y-m-d H:i'));

        if (!$descChanged && !$timeChanged) {
            return redirect()->back()->with('info', 'Tidak ada perubahan pada keterangan maupun waktu transaksi.');
        }

        $reason = !empty($validated['reason']) ? trim($validated['reason']) : 'Koreksi keterangan / waktu transaksi';

        $updateData = ['description' => $newDescription];
        if ($timeChanged) {
            $updateData['transaction_time'] = $newTime;
        }

        $transaction->update($updateData);

        $actionName = $timeChanged ? 'transaction_details_updated' : 'transaction_description_updated';

        \App\Models\AuditLog::create([
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'action' => $actionName,
            'old_values' => [
                'description' => $oldDescription,
                'transaction_time' => $oldTime->toDateTimeString(),
            ],
            'new_values' => [
                'description' => $newDescription,
                'transaction_time' => $newTime->toDateTimeString(),
            ],
            'reason' => $reason,
            'created_at' => now(),
        ]);

        $msg = "Transaksi #{$transaction->id} berhasil diperbarui!";
        if ($descChanged) {
            $msg .= " Keterangan: \"{$newDescription}\".";
        }
        if ($timeChanged) {
            $msg .= " Waktu disesuaikan ke " . $newTime->format('d M Y H:i') . " WIB.";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Reset Seluruh Data ke Kondisi Awal (Khusus Mode Uji Coba)
     */
    public function resetTrialData(Request $request): RedirectResponse
    {
        if (!config('app.trial_mode', true)) {
            abort(403, 'Fitur reset data hanya tersedia selama masa uji coba.');
        }

        while (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            \Illuminate\Support\Facades\DB::rollBack();
        }

        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        $user = \App\Models\User::where('email', 'driver@keuangan.test')->first()
            ?? \App\Models\User::first();
        if ($user) {
            auth()->login($user);
        }

        return redirect()->route('dashboard')->with('success', '✅ Seluruh data berhasil di-reset ke kondisi awal (fresh seeder)! Anda dapat memulai uji coba dari nol.');
    }
}
