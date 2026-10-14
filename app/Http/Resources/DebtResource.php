<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $nextInstallment = $this->relationLoaded('installments')
            ? $this->installments->where('status', '!=', 'paid')->sortBy('due_date')->first()
            : $this->installments()->where('status', '!=', 'paid')->orderBy('due_date')->first();

        $dueDate = $nextInstallment ? Carbon::parse($nextInstallment->due_date) : ($this->started_at ? Carbon::parse($this->started_at) : null);
        $daysUntilDue = $dueDate ? (int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false) : null;
        $isOverdue = $dueDate !== null && $daysUntilDue < 0 && $this->status === 'active';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'platform' => $this->platform,
            'borrower_name' => $this->borrower_name,
            'person_or_institution' => $this->borrower_name ?: $this->platform ?: $this->name,
            'principal_amount' => (int) $this->principal_amount,
            'fee_amount' => (int) $this->fee_amount,
            'total_obligation' => (int) $this->total_obligation,
            'remaining_amount' => (int) $this->remaining_amount,
            'due_date' => $dueDate?->format('Y-m-d'),
            'days_until_due' => $daysUntilDue,
            'is_overdue' => $isOverdue,
            'status' => $this->status,
        ];
    }
}
