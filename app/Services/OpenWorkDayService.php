<?php

namespace App\Services;

use App\Models\WorkDay;
use Illuminate\Support\Carbon;

class OpenWorkDayService
{
    public function openWorkDay(?Carbon $workDate = null, string $dayType = WorkDay::TYPE_ON_BID): WorkDay
    {
        $date = ($workDate ?? now())->toDateString();

        $existingDay = WorkDay::whereDate('work_date', $date)->first();
        if ($existingDay) {
            return $existingDay; // Idempotent: jika sudah buka hari ini, kembalikan data yang ada
        }

        return WorkDay::create([
            'work_date' => $date,
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
            'day_type' => $dayType,
            'opened_at' => now(),
            'closed_at' => null,
            'total_orders' => 0,
            'online_hours' => 0.0,
            'is_performance_eligible' => false,
        ]);
    }
}
