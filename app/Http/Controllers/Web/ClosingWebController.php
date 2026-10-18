<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\UnresolvedDiscrepancyException;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;
use App\Services\ClosingDayService;
use App\Services\ReconciliationEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClosingWebController extends Controller
{
    public function show(int $id, ReconciliationEngineService $reconciliationService): View
    {
        $workDay = WorkDay::with('voiceMemos')->findOrFail($id);
        $accounts = Account::active()
            ->whereIn('type', [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET])
            ->get();
        $denominations = CashDenomination::where('work_day_id', $workDay->id)->first();
        $isPreFilledFromPrevious = false;
        $previousDenominationDate = null;

        if (!$denominations || $denominations->calculateTotal() === 0) {
            // 1. Cari CashDenomination dari shift lain di database yang memiliki total > 0
            $previousDenomination = CashDenomination::with('workDay')
                ->where('work_day_id', '!=', $workDay->id)
                ->where('total_physical_amount', '>', 0)
                ->latest('id')
                ->first();

            $sourceData = null;
            $sourceLabel = null;

            if ($previousDenomination) {
                $sourceData = $previousDenomination->toArray();
                $sourceLabel = "Shift " . ($previousDenomination->workDay?->work_date?->translatedFormat('d M Y') ?? 'Sebelumnya');
            } else {
                // 2. Jika belum pernah closing sama sekali, cek saldo awal kas di akun Cash (metadata atau audit log)
                $cashAcc = Account::where('name', 'Cash')->orWhere('type', \App\Enums\AccountType::CASH)->first();
                if ($cashAcc) {
                    $savedDenom = $cashAcc->metadata['denominations'] ?? null;
                    if (!$savedDenom) {
                        $auditLog = \App\Models\AuditLog::where('auditable_type', Account::class)
                            ->where('auditable_id', $cashAcc->id)
                            ->whereNotNull('new_values->denominations')
                            ->latest('id')
                            ->first();
                        $savedDenom = $auditLog?->new_values['denominations'] ?? null;
                    }

                    if (!empty($savedDenom) && CashDenomination::calculateSum($savedDenom) > 0) {
                        $sourceData = $savedDenom;
                        $sourceLabel = "Saldo Modal Awal Kas Terkunci";
                    } elseif ($cashAcc->current_balance > 0 || $cashAcc->initial_balance > 0) {
                        // 3. Fallback dekomposisi otomatis dari saldo kas berjalan
                        $cashBal = $cashAcc->current_balance > 0 ? $cashAcc->current_balance : $cashAcc->initial_balance;
                        $sourceData = CashDenomination::decomposeAmount($cashBal);
                        $sourceLabel = "Saldo Kas Berjalan (Rp " . number_format($cashBal, 0, ',', '.') . ")";
                    }
                }
            }

            if ($sourceData) {
                if (!$denominations) {
                    $denominations = new CashDenomination(['work_day_id' => $workDay->id]);
                }
                $denominations->bill_100000 = (int) ($sourceData['bill_100000'] ?? 0);
                $denominations->bill_50000 = (int) ($sourceData['bill_50000'] ?? 0);
                $denominations->bill_20000 = (int) ($sourceData['bill_20000'] ?? 0);
                $denominations->bill_10000 = (int) ($sourceData['bill_10000'] ?? 0);
                $denominations->bill_5000 = (int) ($sourceData['bill_5000'] ?? 0);
                $denominations->bill_2000 = (int) ($sourceData['bill_2000'] ?? 0);
                $denominations->bill_1000 = (int) ($sourceData['bill_1000'] ?? 0);
                $denominations->coin_1000 = (int) ($sourceData['coin_1000'] ?? 0);
                $denominations->coin_500 = (int) ($sourceData['coin_500'] ?? 0);
                $denominations->coin_200 = (int) ($sourceData['coin_200'] ?? 0);
                $denominations->coin_100 = (int) ($sourceData['coin_100'] ?? 0);
                $denominations->total_physical_amount = CashDenomination::calculateSum($denominations->toArray());
                $denominations->damaged_notes_amount = (int) ($sourceData['damaged_notes_amount'] ?? 0);
                $denominations->damaged_notes_note = $sourceData['damaged_notes_note'] ?? null;

                $isPreFilledFromPrevious = true;
                $previousDenominationDate = $sourceLabel;
            } else {
                if (!$denominations) {
                    $denominations = new CashDenomination(['work_day_id' => $workDay->id]);
                }
            }
        }

        $reconciliationReport = $reconciliationService->reconcileWorkDay($workDay, []);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashTransactions = \App\Models\Transaction::where('work_day_id', $workDay->id)
            ->whereHas('ledgerEntries', function ($q) use ($cashAccount) {
                if ($cashAccount) {
                    $q->where('account_id', $cashAccount->id);
                }
            })
            ->with(['ledgerEntries.account'])
            ->orderBy('transaction_time', 'desc')
            ->get();

        // 1. Data Pengeluaran Parkir Shift Ini
        $parkingTransactions = \App\Models\Transaction::where('work_day_id', $workDay->id)
            ->where('metadata->type', 'quick_parking')
            ->get();

        $dayParkingExpense = 0;
        $dayRestoPaidParkingCount = 0;
        $dayPaidParkingCount = 0;

        foreach ($parkingTransactions as $pTx) {
            $pMeta = $pTx->metadata ?? [];
            if (!empty($pMeta['is_covered_by_resto'])) {
                $dayRestoPaidParkingCount++;
            } else {
                $dayParkingExpense += $pTx->amount;
                $dayPaidParkingCount++;
            }
        }

        // 2. Snapshot Checkpoint ShopeeFood Driver Terakhir
        $latestSnapshot = \App\Models\DriverSnapshot::where('work_day_id', $workDay->id)
            ->latest('recorded_at')
            ->first();

        // 3. Akumulasi Tip, QRIS Inflow, & Surplus Shift Ini
        $dayTransactions = \App\Models\Transaction::where('work_day_id', $workDay->id)->get();
        $gopaySurplus = 0;
        $gopayMerchantSurplus = 0;
        $cashSurplus = 0;

        foreach ($dayTransactions as $tx) {
            $type = $tx->metadata['type'] ?? $tx->type;
            $meta = $tx->metadata ?? [];
            if ($type === 'digital_reconciliation_surplus') {
                $accId = (int) ($meta['account_id'] ?? 0);
                $acc = $accounts->firstWhere('id', $accId);
                $diffAmount = (int) ($meta['diff'] ?? $tx->amount);
                if ($acc) {
                    if ($acc->name === 'GoPay') {
                        $gopaySurplus += $diffAmount;
                    } elseif ($acc->name === 'GoPay Merchant') {
                        $gopayMerchantSurplus += $diffAmount;
                    } elseif ($acc->name === 'Cash' || $acc->type === \App\Enums\AccountType::CASH) {
                        $cashSurplus += $diffAmount;
                    }
                }
            }
        }

        $shopeeFoodTip = (int) ($latestSnapshot?->customer_tips ?? 0);
        $totalTipsAndQris = $shopeeFoodTip + $gopaySurplus + $gopayMerchantSurplus + $cashSurplus;
        $parkingNetResult = $totalTipsAndQris - $dayParkingExpense;

        // 4. Daftar Akun Wadah untuk Sinkronisasi di Closing
        $digitalReconAccounts = Account::whereIn('type', [\App\Enums\AccountType::CASH, \App\Enums\AccountType::BANK, \App\Enums\AccountType::EWALLET])
            ->active()
            ->orderByRaw("CASE 
                WHEN name = 'ShopeePay' THEN 1 
                WHEN name = 'Cash' THEN 2 
                WHEN name = 'GoPay' THEN 3 
                WHEN name = 'GoPay Merchant' THEN 4 
                ELSE 5 END")
            ->get();

        $todayCodQrisBillTotal = (int) \App\Models\ActiveCodOrder::where('work_day_id', $workDay->id)
            ->where(function ($q) {
                $q->where('payment_method', 'qris')
                  ->orWhere('status', 'on_delivery');
            })
            ->sum('bill_amount');

        return view('closing.show', compact(
            'workDay',
            'accounts',
            'cashAccount',
            'denominations',
            'reconciliationReport',
            'cashTransactions',
            'parkingTransactions',
            'dayParkingExpense',
            'dayRestoPaidParkingCount',
            'dayPaidParkingCount',
            'latestSnapshot',
            'shopeeFoodTip',
            'gopaySurplus',
            'gopayMerchantSurplus',
            'cashSurplus',
            'totalTipsAndQris',
            'parkingNetResult',
            'digitalReconAccounts',
            'todayCodQrisBillTotal',
            'isPreFilledFromPrevious',
            'previousDenominationDate'
        ));
    }

    /**
     * Simpan Denominasi Uang Tunai Fisik di Dompet
     */
    public function saveDenominations(Request $request, int $id): RedirectResponse
    {
        $workDay = WorkDay::findOrFail($id);

        $validated = $request->validate([
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

        $denomination = CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
        $denomination->fill($validated);
        $denomination->total_physical_amount = $denomination->calculateTotal();
        $denomination->save();

        // Sinkronkan ke metadata akun kas agar selalu tercatat
        $cashAcc = Account::where('name', 'Cash')->orWhere('type', \App\Enums\AccountType::CASH)->first();
        if ($cashAcc) {
            $meta = $cashAcc->metadata ?? [];
            $meta['denominations'] = $denomination->toArray();
            $cashAcc->update(['metadata' => $meta]);
        }

        return redirect()->back()->with('success', 'Perhitungan fisik uang dompet berhasil disimpan.');
    }

    /**
     * Finalisasi Hari Kerja (Mendukung rekonsiliasi saldo digital & snapshot sekaligus dalam satu klik)
     */
    public function finalize(
        Request $request,
        int $id,
        ClosingDayService $closingService,
        \App\Services\DigitalReconciliationService $digitalReconciliationService
    ): RedirectResponse {
        $workDay = WorkDay::findOrFail($id);

        if ($workDay->isFinal()) {
            return redirect()->route('dashboard')->with('info', "Hari kerja {$workDay->work_date->toDateString()} sudah pernah difinalisasi.");
        }

        $validated = $request->validate([
            'closing_notes' => 'nullable|string',
            'cod_qris_amount' => 'nullable|integer|min:0',
            'digital_balances' => 'nullable|array',
            'digital_balances.*.account_id' => 'required_with:digital_balances|exists:accounts,id',
            'digital_balances.*.actual_balance' => 'required_with:digital_balances|integer|min:0',
            'balances' => 'nullable|array',
            'balances.*.account_id' => 'required_with:balances|exists:accounts,id',
            'balances.*.actual_balance' => 'required_with:balances|integer|min:0',
            'completed_orders' => 'nullable|integer|min:0',
            'delivery_fee' => 'nullable|integer|min:0',
            'hub_income' => 'nullable|integer|min:0',
            'hub_bonus' => 'nullable|integer|min:0',
            'customer_tips' => 'nullable|integer|min:0',
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

        // 1. Simpan Denominasi Fisik jika disertakan dalam submit
        $denomKeys = ['bill_100000', 'bill_50000', 'bill_20000', 'bill_10000', 'bill_5000', 'bill_2000', 'bill_1000', 'coin_1000', 'coin_500', 'coin_200', 'coin_100'];
        $hasDenomInput = false;
        foreach ($denomKeys as $dk) {
            if ($request->has($dk)) {
                $hasDenomInput = true;
                break;
            }
        }

        if ($hasDenomInput) {
            $denomination = CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
            foreach ($denomKeys as $dk) {
                $denomination->{$dk} = (int) ($validated[$dk] ?? 0);
            }
            $denomination->damaged_notes_amount = (int) ($validated['damaged_notes_amount'] ?? 0);
            $denomination->damaged_notes_note = $validated['damaged_notes_note'] ?? null;
            $denomination->total_physical_amount = $denomination->calculateTotal();
            $denomination->save();

            $cashAcc = Account::where('name', 'Cash')->orWhere('type', \App\Enums\AccountType::CASH)->first();
            if ($cashAcc) {
                $meta = $cashAcc->metadata ?? [];
                $meta['denominations'] = $denomination->toArray();
                $cashAcc->update(['metadata' => $meta]);
            }
        }

        // 2. Rekonsiliasi Saldo Digital & Simpan DriverSnapshot
        $balances = $validated['balances'] ?? $validated['digital_balances'] ?? [];
        $codQrisAmount = (int) ($validated['cod_qris_amount'] ?? 0);

        if (!empty($balances)) {
            $digitalReconciliationService->reconcileDigitalAccounts($workDay, $balances, $codQrisAmount);

            // Simpan DriverSnapshot shift ini
            $shopeepayBalance = 0;
            $gopayBalance = 0;
            $gopayMerchantBalance = 0;

            foreach ($balances as $b) {
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

            $cashAcc = Account::where('name', 'Cash')->first();
            $cashBalance = $cashAcc ? (int) $cashAcc->current_balance : 0;

            \App\Models\DriverSnapshot::create([
                'work_day_id' => $workDay->id,
                'recorded_at' => now(),
                'completed_orders' => (int) ($validated['completed_orders'] ?? 0),
                'delivery_fee' => (int) ($validated['delivery_fee'] ?? 0),
                'hub_income' => (int) ($validated['hub_income'] ?? 0),
                'hub_bonus' => (int) ($validated['hub_bonus'] ?? 0),
                'customer_tips' => (int) ($validated['customer_tips'] ?? 0),
                'shopeepay_balance' => $shopeepayBalance,
                'gopay_balance' => $gopayBalance,
                'gopay_merchant_balance' => $gopayMerchantBalance,
                'cash_balance' => $cashBalance,
                'notes' => $validated['closing_notes'] ?? null,
            ]);
        }

        try {
            $closingService->finalizeWorkDay(
                workDay: $workDay,
                digitalBalances: $balances,
                closingNotes: $validated['closing_notes'] ?? null
            );

            return redirect()->route('dashboard')->with('success', "Hari kerja {$workDay->work_date->toDateString()} berhasil difinalisasi! Seluruh saldo rekening telah disinkronkan.");
        } catch (UnresolvedDiscrepancyException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Sesuaikan Kas Hilang / Lebih Tak Terlacak
     */
    public function adjustDiscrepancy(
        Request $request,
        int $id,
        \App\Services\DiscrepancyResolutionService $resolutionService
    ): RedirectResponse {
        $workDay = WorkDay::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $physicalRecord = CashDenomination::where('work_day_id', $workDay->id)->first();
        $physicalCashActual = $physicalRecord ? (int) $physicalRecord->total_physical_amount : 0;
        $discrepancy = $physicalCashActual - (int) $cashAccount->current_balance;

        if ($discrepancy === 0) {
            return redirect()->back()->with('error', 'Saldo kas sudah seimbang (selisih Rp0), tidak perlu penyesuaian.');
        }

        $resolutionService->adjustUnresolvedCashDiscrepancy(
            workDay: $workDay,
            cashAccount: $cashAccount,
            discrepancy: $discrepancy,
            reason: $validated['reason']
        );

        return redirect()->back()->with('success', 'Penyesuaian selisih kas fisik berhasil dicatat. Saldo kas kini telah seimbang dan siap difinalisasi!');
    }

    /**
     * Koreksi Transaksi Kas Langsung di Layar Closing (In-Place Edit)
     */
    public function correctTransaction(
        Request $request,
        int $id,
        int $transactionId,
        \App\Services\ReversalService $reversalService,
        \App\Services\LedgerService $ledgerService
    ): RedirectResponse {
        $workDay = WorkDay::findOrFail($id);
        $originalTx = \App\Models\Transaction::with('ledgerEntries')
            ->where('work_day_id', $workDay->id)
            ->findOrFail($transactionId);

        $validated = $request->validate([
            'new_amount' => 'required|integer|min:0',
            'reason' => 'required|string|min:5',
        ]);

        $newAmount = (int) $validated['new_amount'];
        $reason = trim($validated['reason']);

        if ($originalTx->type === 'reversal') {
            return redirect()->back()->with('error', 'Transaksi pembalik (reversal) tidak dapat dikoreksi.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use (
            $workDay,
            $originalTx,
            $newAmount,
            $reason,
            $reversalService,
            $ledgerService
        ) {
            // 1. Batalkan (reverse) transaksi lama secara akuntansi
            $reversalService->reverseTransaction(
                originalTransaction: $originalTx,
                reason: "Koreksi closing: {$reason}"
            );

            // 2. Jika nominal baru > 0, buat transaksi baru pengganti yang valid
            if ($newAmount > 0) {
                $newEntries = [];
                foreach ($originalTx->ledgerEntries as $entry) {
                    $newEntries[] = [
                        'account_id' => $entry->account_id,
                        'entry_type' => $entry->entry_type,
                        'amount' => $newAmount,
                    ];
                }

                $desc = "Koreksi #{$originalTx->id}: {$originalTx->description}";
                $ledgerService->recordTransaction(
                    workDay: $workDay,
                    type: $originalTx->type,
                    description: $desc,
                    transactionTime: $originalTx->transaction_time,
                    entries: $newEntries,
                    isEstimatedTime: false,
                    metadata: array_merge($originalTx->metadata ?? [], [
                        'corrected_from_transaction_id' => $originalTx->id,
                        'correction_reason' => $reason,
                        'original_amount' => $originalTx->amount,
                        'new_amount' => $newAmount,
                    ])
                );
            }

            // 3. Catat ke AuditLog
            \App\Models\AuditLog::create([
                'auditable_type' => \App\Models\Transaction::class,
                'auditable_id' => $originalTx->id,
                'action' => 'in_place_closing_correction',
                'old_values' => ['amount' => $originalTx->amount],
                'new_values' => ['amount' => $newAmount],
                'reason' => $reason,
            ]);
        });

        return redirect()->back()->with('success', "Transaksi #{$originalTx->id} berhasil dikoreksi menjadi Rp " . number_format($newAmount, 0, ',', '.') . ". Selisih kas otomatis disesuaikan!");
    }
}
