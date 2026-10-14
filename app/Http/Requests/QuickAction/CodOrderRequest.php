<?php

namespace App\Http\Requests\QuickAction;

use Illuminate\Foundation\Http\FormRequest;

class CodOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['sometimes', 'required', 'integer', 'exists:work_days,id'],
            'order_amount' => ['sometimes', 'required_without:bill_amount', 'integer', 'min:1000'],
            'bill_amount' => ['sometimes', 'required_without:order_amount', 'integer', 'min:1'],
            'cash_collected' => ['sometimes', 'required_without:received_amount', 'integer', 'gte:order_amount'],
            'received_amount' => ['sometimes', 'required_without:cash_collected', 'integer', 'gte:bill_amount'],
            'order_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_amount.min' => 'Tagihan pesanan COD minimal Rp1.000.',
            'bill_amount.min' => 'Tagihan pesanan COD minimal Rp1.',
            'cash_collected.gte' => 'Uang tunai yang diterima tidak boleh kurang dari nominal tagihan pesanan COD.',
            'received_amount.gte' => 'Uang tunai yang diterima tidak boleh kurang dari nominal tagihan pesanan COD.',
        ];
    }
}
