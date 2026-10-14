<?php

namespace App\Http\Requests\QuickAction;

use Illuminate\Foundation\Http\FormRequest;

class QuickFuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['sometimes', 'required', 'integer', 'exists:work_days,id'],
            'type' => ['required_without:vendor_type', 'string', 'in:spbu,madura'],
            'vendor_type' => ['required_without:type', 'string', 'in:spbu,madura'],
            'amount' => ['required', 'integer', 'min:1000'],
            'account_id' => ['required_without:source_account_id', 'integer', 'exists:accounts,id'],
            'source_account_id' => ['required_without:account_id', 'integer', 'exists:accounts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Jenis pengisian bensin harus berupa SPBU atau Madura.',
            'vendor_type.in' => 'Jenis pengisian bensin harus berupa SPBU atau Madura.',
            'amount.min' => 'Nominal bensin minimal Rp1.000.',
            'source_account_id.exists' => 'Akun sumber pembayaran tidak valid.',
            'account_id.exists' => 'Akun sumber pembayaran tidak valid.',
        ];
    }
}
