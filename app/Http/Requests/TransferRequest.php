<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'integer', 'min:1'],
            'admin_fee' => ['nullable', 'integer', 'min:0'],
            'transaction_time' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_account_id.different' => 'Akun tujuan transfer tidak boleh sama dengan akun sumber.',
            'amount.min' => 'Nominal transfer minimal Rp1.',
            'amount.integer' => 'Nominal transfer harus berupa bilangan bulat Rupiah.',
        ];
    }
}
