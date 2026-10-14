<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDriverSnapshotRequest;
use App\Models\DriverSnapshot;
use App\Models\WorkDay;
use Illuminate\Http\JsonResponse;

class DriverSnapshotController extends Controller
{
    public function store(StoreDriverSnapshotRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $snapshot = DriverSnapshot::create([
            'work_day_id' => $workDay->id,
            'recorded_at' => now(),
            'completed_orders' => $validated['completed_orders'],
            'hub_income' => $validated['hub_income'],
            'hub_bonus' => $validated['hub_bonus'],
            'customer_tips' => $validated['customer_tips'],
            'shopeepay_balance' => $validated['shopeepay_balance'],
            'gopay_balance' => $validated['gopay_balance'],
            'gopay_merchant_balance' => $validated['gopay_merchant_balance'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Snapshot progres driver berhasil disimpan.',
            'data' => [
                'snapshot_id' => $snapshot->id,
                'total_driver_income' => $snapshot->total_driver_income,
                'total_digital_holding' => $snapshot->total_digital_holding,
                'merchant_settlement_status' => $snapshot->merchant_settlement_status,
                'recorded_at' => $snapshot->recorded_at->toISOString(),
            ],
        ], 201);
    }
}
