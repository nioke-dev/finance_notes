<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuickParkingRequest;
use App\Models\WorkDay;
use App\Services\QuickParkingService;
use Illuminate\Http\JsonResponse;

class QuickParkingController extends Controller
{
    public function store(
        QuickParkingRequest $request,
        QuickParkingService $parkingService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $amount = $validated['amount'] ?? QuickParkingService::DEFAULT_PARKING_FEE;

        $transaction = $parkingService->recordParking(
            workDay: $workDay,
            amount: $amount,
            restaurantName: $validated['restaurant_name'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Biaya parkir berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'restaurant_name' => $validated['restaurant_name'] ?? null,
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
