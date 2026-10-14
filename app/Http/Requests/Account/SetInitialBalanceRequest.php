<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class SetInitialBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'initial_balance' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'initial_balance.required' => 'Saldo awal wajib diisi.',
            'initial_balance.integer' => 'Saldo awal harus berupa bilangan bulat Rupiah.',
            'initial_balance.min' => 'Saldo awal tidak boleh bernilai negatif.',
        ];
    }
}
