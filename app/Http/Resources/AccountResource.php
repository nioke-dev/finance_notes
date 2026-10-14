<?php

namespace App\Http\Resources;

use App\Enums\AccountType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'type' => $this->type instanceof AccountType ? $this->type->value : (string) $this->type,
            'current_balance' => (int) $this->current_balance,
            'initial_balance' => (int) $this->initial_balance,
            'is_active' => (bool) $this->is_active,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
