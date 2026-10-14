<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DebtInstallment;
use Illuminate\Http\JsonResponse;

class DebtScheduleController extends Controller
{
    public function upcoming(): JsonResponse
    {
        $startDate = now()->startOfDay();
        $endDate = now()->addDays(30)->endOfDay();

        $installments = DebtInstallment::with('debt')
            ->where('status', 'unpaid')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->orderBy('due_date', 'asc')
            ->get();

        $totalObligation = (int) $installments->sum('amount_due');

        $data = $installments->map(function (DebtInstallment $item) {
            $daysRemaining = (int) now()->startOfDay()->diffInDays($item->due_date, false);

            return [
                'installment_id' => $item->id,
                'debt_id' => $item->debt_id,
                'debt_name' => $item->debt->name,
                'platform' => $item->debt->platform,
                'installment_number' => $item->installment_number,
                'total_tenor' => $item->debt->tenor_months,
                'amount_due' => $item->amount_due,
                'due_date' => $item->due_date->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_upcoming_obligation' => $totalObligation,
                'installments' => $data,
            ],
        ], 200);
    }
}
