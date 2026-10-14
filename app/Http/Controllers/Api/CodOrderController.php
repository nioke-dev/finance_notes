<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCodOrderRequest;
use App\Models\WorkDay;
use App\Services\CodOrderService;
use Illuminate\Http\JsonResponse;

class CodOrderController extends Controller
{
    public function store(
        StoreCodOrderRequest $request,
        CodOrderService $codService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $transaction = $codService->recordCodOrder(
            workDay: $workDay,
            billAmount: (int) $validated['bill_amount'],
            receivedAmount: (int) $validated['received_amount'],
            orderNumber: $validated['order_number'] ?? null
        );

        $surplus = (int) $validated['received_amount'] - (int) $validated['bill_amount'];

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi COD berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'bill_amount' => (int) $validated['bill_amount'],
                'received_amount' => (int) $validated['received_amount'],
                'surplus_tip' => $surplus,
                'order_number' => $validated['order_number'] ?? null,
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
