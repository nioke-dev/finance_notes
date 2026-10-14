<?php

namespace App\Http\Requests\Debt;

use Illuminate\Foundation\Http\FormRequest;

class PayDebtInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debt_installment_id' => ['required', 'exists:debt_installments,id'],
            'payment_account_id' => ['required', 'exists:accounts,id'],
            'amount_paid' => ['required', 'integer', 'min:1'],
            'promo_discount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount_paid.min' => 'Nominal pembayaran cicilan minimal Rp1.',
            'payment_account_id.exists' => 'Akun sumber pembayaran tidak valid.',
        ];
    }
}
