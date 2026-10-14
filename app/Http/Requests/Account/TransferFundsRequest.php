<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class TransferFundsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_account_id' => ['required', 'exists:accounts,id'],
            'destination_account_id' => ['required', 'exists:accounts,id', 'different:source_account_id'],
            'amount' => ['required', 'integer', 'min:1'],
            'admin_fee' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_account_id.different' => 'Akun tujuan transfer tidak boleh sama dengan akun sumber.',
            'amount.min' => 'Nominal transfer minimal Rp1.',
            'amount.integer' => 'Nominal transfer harus berupa bilangan bulat Rupiah.',
        ];
    }
}
