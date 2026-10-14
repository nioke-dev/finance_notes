<?php

namespace App\Services;

use App\Exceptions\UnresolvedDiscrepancyException;
use App\Models\WorkDay;
use InvalidArgumentException;

class ClosingDayService
{
    protected ReconciliationEngineService $reconciliationService;

    public function __construct(ReconciliationEngineService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
    }

    public function finalizeWorkDay(WorkDay $workDay, array $digitalBalances, ?string $closingNotes = null): WorkDay
    {
        if ($workDay->isFinal()) {
            throw new InvalidArgumentException("Hari kerja ini sudah pernah difinalisasi sebelumnya.");
        }

        $report = $this->reconciliationService->reconcileWorkDay($workDay, $digitalBalances);

        if (!$report['is_balanced']) {
            $unmatched = array_filter($report['accounts'], fn ($a) => $a['discrepancy'] !== 0);
            throw new UnresolvedDiscrepancyException(array_values($unmatched));
        }

        $workDay->update([
            'status' => WorkDay::STATUS_FINAL,
            'closed_at' => now(),
            'closing_notes' => $closingNotes,
        ]);

        return $workDay->fresh();
    }
}
