<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeWorkDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'digital_balances' => ['present', 'array'],
            'digital_balances.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'digital_balances.*.actual_balance' => ['required', 'integer', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
