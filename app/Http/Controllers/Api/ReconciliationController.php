<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\UnresolvedDiscrepancyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinalizeWorkDayRequest;
use App\Models\WorkDay;
use App\Services\ClosingDayService;
use Illuminate\Http\JsonResponse;

class ReconciliationController extends Controller
{
    public function finalizeDay(
        FinalizeWorkDayRequest $request,
        ClosingDayService $closingService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        try {
            $updatedWorkDay = $closingService->finalizeWorkDay(
                workDay: $workDay,
                digitalBalances: $validated['digital_balances'],
                closingNotes: $validated['closing_notes'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Hari kerja berhasil difinalisasi. Selisih Rp0.',
                'data' => [
                    'work_day_id' => $updatedWorkDay->id,
                    'status' => $updatedWorkDay->status,
                    'closed_at' => $updatedWorkDay->closed_at->toISOString(),
                ],
            ], 200);
        } catch (UnresolvedDiscrepancyException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'discrepant_accounts' => $e->getDiscrepantAccounts(),
            ], 422);
        }
    }
}
