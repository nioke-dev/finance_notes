<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $calculatedAmount = $this->amount
            ?? ($this->relationLoaded('ledgerEntries') ? $this->ledgerEntries->where('entry_type.value', 'debit')->sum('amount') : 0);

        return [
            'id' => (int) $this->id,
            'reference_number' => $this->reference_number ?? ('TX-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT)),
            'work_day_id' => (int) $this->work_day_id,
            'type' => (string) $this->type,
            'description' => (string) $this->description,
            'transaction_time' => $this->transaction_time?->toISOString(),
            'formatted_time' => $this->transaction_time ? $this->transaction_time->format('H:i') . ' WIB' : null,
            'amount' => (int) $calculatedAmount,
            'is_estimated_time' => (bool) $this->is_estimated_time,
            'is_reversal' => $this->type === 'reversal' || isset($this->metadata['reversed_transaction_id']),
            'metadata' => $this->metadata,
            'ledger_entries' => $this->whenLoaded('ledgerEntries', function () {
                return $this->ledgerEntries->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'account_id' => $entry->account_id,
                        'entry_type' => is_string($entry->entry_type) ? $entry->entry_type : $entry->entry_type->value,
                        'amount' => (int) $entry->amount,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
