<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluatePerformanceRequest;
use App\Models\WorkDay;
use App\Services\ShopeeBonusCalculatorService;
use Illuminate\Http\JsonResponse;

class DriverPerformanceController extends Controller
{
    public function evaluate(
        EvaluatePerformanceRequest $request,
        ShopeeBonusCalculatorService $calculatorService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $isEligible = ($validated['online_hours'] >= 9.0)
            && ($validated['peak_hour_rate'] >= 70.0)
            && ($validated['completion_rate'] >= 80.0);

        $calculatedBonus = $isEligible 
            ? $calculatorService->calculateBonus($validated['eligible_orders_count']) 
            : 0;

        $workDay->update([
            'online_hours' => $validated['online_hours'],
            'is_performance_eligible' => $isEligible,
            'total_orders' => $validated['eligible_orders_count'],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'work_day_id' => $workDay->id,
                'is_eligible' => $isEligible,
                'calculated_bonus' => $calculatedBonus,
                'message' => $isEligible 
                    ? "Performa memenuhi syarat. Bonus Hub Rp{$calculatedBonus} diakui di estimasi pendapatan hari ini."
                    : "Waktu online atau performa belum memenuhi syarat. Bonus Hub hari ini Rp0.",
            ],
        ], 200);
    }
}
