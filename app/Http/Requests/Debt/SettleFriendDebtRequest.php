<?php

namespace App\Http\Requests\Debt;

use Illuminate\Foundation\Http\FormRequest;

class SettleFriendDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debt_id' => ['required', 'exists:debts,id'],
            'amount_received' => ['required', 'integer', 'min:1'],
            'destination_account_id' => ['required', 'exists:accounts,id'],
            'forgive_remainder' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_received.min' => 'Nominal uang yang diterima minimal Rp1.',
            'forgive_remainder.boolean' => 'Opsi pengiklasan sisa utang harus bernilai boolean.',
        ];
    }
}
