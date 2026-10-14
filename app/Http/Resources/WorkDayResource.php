<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkDayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workDate = $this->work_date;
        $totalOrders = (int) ($this->total_orders ?? $this->total_trips ?? $this->latestSnapshot?->completed_orders ?? 0);

        $shopeeHub = (int) ($this->shopee_hub_income ?? $this->latestSnapshot?->hub_income ?? 0);
        $hubBonus = (int) ($this->bonus_amount ?? $this->latestSnapshot?->hub_bonus ?? 0);
        $shopeeTip = (int) ($this->shopee_tip_amount ?? $this->latestSnapshot?->customer_tips ?? 0);
        $totalShopeeIncome = (int) ($this->total_shopee_income ?? ($shopeeHub + $hubBonus + $shopeeTip));

        return [
            'id' => $this->id,
            'date' => $workDate?->format('Y-m-d'),
            'formatted_date' => $workDate?->translatedFormat('l, d M Y'),
            'status' => $this->status,
            'total_trips' => $totalOrders,
            'total_shopee_income' => $totalShopeeIncome,
            'is_balanced' => $this->status === 'final',
            'closing_notes' => $this->closing_notes,
        ];
    }
}
