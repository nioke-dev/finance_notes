<?php

namespace App\Http\Requests\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'exists:work_days,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'category_id' => ['required', 'exists:categories,id'],
            'source_account_id' => ['required', 'exists:accounts,id'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan koreksi/transaksi terlambat wajib diisi untuk audit trail.',
            'reason.min' => 'Alasan minimal terdiri dari 5 karakter.',
            'amount.min' => 'Nominal transaksi minimal Rp1.',
        ];
    }
}
