<?php

namespace App\Http\Requests;

use App\Http\Requests\QuickAction\CodOrderRequest;

class StoreCodOrderRequest extends CodOrderRequest
{
    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'order_number' => ['nullable', 'string', 'max:50'],
            'bill_amount' => ['required', 'integer', 'min:1'],
            'received_amount' => ['required', 'integer', 'gte:bill_amount'],
        ];
    }
}
