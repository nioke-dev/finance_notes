<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FriendDebtPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'debt_id' => ['required', 'integer', 'exists:debts,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'received_amount' => ['required', 'integer', 'min:1'],
            'forgive_remainder' => ['required', 'boolean'],
        ];
    }
}
