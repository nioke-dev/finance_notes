<?php

namespace App\Http\Requests\Reconciliation;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'exists:work_days,id'],
            'denominations' => ['required', 'array'],
            'denominations.*.nominal' => ['required', 'integer', 'in:100000,50000,20000,10000,5000,2000,1000,500,200,100'],
            'denominations.*.quantity' => ['required', 'integer', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'denominations.*.nominal.in' => 'Pecahan uang harus sesuai denominasi resmi Rupiah.',
            'denominations.*.quantity.min' => 'Jumlah lembar/keping uang tidak boleh negatif.',
        ];
    }
}
