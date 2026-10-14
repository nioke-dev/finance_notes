<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;
use App\Services\ReconciliationEngineService;
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
}
