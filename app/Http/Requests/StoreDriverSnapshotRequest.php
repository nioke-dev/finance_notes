<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'completed_orders' => ['required', 'integer', 'min:0'],
            'hub_income' => ['required', 'integer', 'min:0'],
            'hub_bonus' => ['required', 'integer', 'min:0'],
            'customer_tips' => ['required', 'integer', 'min:0'],
            'shopeepay_balance' => ['required', 'integer', 'min:0'],
            'gopay_balance' => ['required', 'integer', 'min:0'],
            'gopay_merchant_balance' => ['required', 'integer', 'min:0'],
        ];
    }
}
