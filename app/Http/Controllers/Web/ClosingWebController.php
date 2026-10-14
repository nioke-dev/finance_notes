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
        $accounts = Account::active()->get();
        $denominations = CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
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

        return view('closing.show', compact(
            'workDay',
            'accounts',
            'cashAccount',
            'denominations',
            'reconciliationReport',
            'cashTransactions'
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

        return redirect()->back()->with('success', 'Perhitungan fisik uang dompet berhasil disimpan.');
    }

    /**
     * Finalisasi Hari Kerja
     */
    public function finalize(
        Request $request,
        int $id,
        ClosingDayService $closingService
    ): RedirectResponse {
        $workDay = WorkDay::findOrFail($id);

        $validated = $request->validate([
            'closing_notes' => 'nullable|string',
            'digital_balances' => 'nullable|array',
        ]);

        try {
            $closingService->finalizeWorkDay(
                workDay: $workDay,
                digitalBalances: $validated['digital_balances'] ?? [],
                closingNotes: $validated['closing_notes'] ?? null
            );

            return redirect()->route('dashboard')->with('success', "Hari kerja {$workDay->work_date->toDateString()} berhasil difinalisasi!");
        } catch (UnresolvedDiscrepancyException $e) {
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
