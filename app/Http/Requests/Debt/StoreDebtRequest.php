<?php

namespace App\Http\Requests\Debt;

use Illuminate\Foundation\Http\FormRequest;

class StoreDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:payable,receivable'],
            'person_or_institution' => ['required', 'string', 'max:100'],
            'principal_amount' => ['required', 'integer', 'min:1000'],
            'admin_fee' => ['nullable', 'integer', 'min:0'],
            'disbursement_account_id' => ['required', 'exists:accounts,id'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Tipe kewajiban harus payable (utang) atau receivable (piutang).',
            'principal_amount.min' => 'Pokok pinjaman minimal Rp1.000.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo tidak boleh berada di masa lalu.',
        ];
    }
}
