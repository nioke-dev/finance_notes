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

        return view('closing.show', compact(
            'workDay',
            'accounts',
            'denominations',
            'reconciliationReport'
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
}
