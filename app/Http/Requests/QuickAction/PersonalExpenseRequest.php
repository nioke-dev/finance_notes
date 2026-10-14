<?php

namespace App\Http\Requests\QuickAction;

use Illuminate\Foundation\Http\FormRequest;

class PersonalExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['sometimes', 'required', 'integer', 'exists:work_days,id'],
            'amount' => ['required', 'integer', 'min:500'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'source_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Nominal pengeluaran minimal Rp500.',
            'category_id.required' => 'Kategori pengeluaran wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'account_id.exists' => 'Akun sumber pembayaran tidak valid.',
            'source_account_id.exists' => 'Akun sumber pembayaran tidak valid.',
        ];
    }
}
