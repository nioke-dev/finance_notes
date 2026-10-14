<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkDay;
use App\Services\ReconciliationEngineService;
use Illuminate\Http\JsonResponse;

class UnfinalizedDatesBannerController extends Controller
{
    public function index(ReconciliationEngineService $reconciliationService): JsonResponse
    {
        $today = now()->toDateString();

        $pendingDays = WorkDay::where('status', WorkDay::STATUS_NEEDS_RECONCILIATION)
            ->whereDate('work_date', '<', $today)
            ->orderBy('work_date', 'asc')
            ->get();

        $bannerData = $pendingDays->map(function (WorkDay $day) use ($reconciliationService) {
            $report = $reconciliationService->reconcileWorkDay($day, []);

            return [
                'work_day_id' => $day->id,
                'work_date' => $day->work_date->format('Y-m-d'),
                'human_date' => $day->work_date->translatedFormat('d M Y'),
                'status' => $day->status,
                'total_discrepancy' => $report['total_absolute_discrepancy'],
                'message' => "Tanggal {$day->work_date->format('d M')} belum difinalisasi (Selisih Rp" . number_format($report['total_absolute_discrepancy'], 0, ',', '.') . "). Klik untuk selesaikan!",
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $bannerData,
        ], 200);
    }
}
