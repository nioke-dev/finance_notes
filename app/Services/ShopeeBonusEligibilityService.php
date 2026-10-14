<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ShopeeBonusEligibilityService
{
    public const WINDOW_START = '10:00:00';
    public const WINDOW_END = '20:00:00';

    public function isTimeEligible(Carbon $time): bool
    {
        $timeString = $time->format('H:i:s');

        return $timeString >= self::WINDOW_START && $timeString <= self::WINDOW_END;
    }

    /**
     * @param Collection|array $orders Array of ['order_number' => string, 'completed_at' => Carbon|string]
     */
    public function filterEligibleOrders(Collection|array $orders): Collection
    {
        $collection = is_array($orders) ? collect($orders) : $orders;

        return $collection->filter(function ($order) {
            $time = ($order['completed_at'] instanceof Carbon) 
                ? $order['completed_at'] 
                : Carbon::parse($order['completed_at']);

            return $this->isTimeEligible($time);
        })->values();
    }
}
