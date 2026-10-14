<?php

namespace App\Http\Requests\WorkDay;

use Illuminate\Foundation\Http\FormRequest;

class OpenWorkDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required_without:work_date', 'date_format:Y-m-d', 'unique:work_days,work_date'],
            'work_date' => ['required_without:date', 'date_format:Y-m-d', 'unique:work_days,work_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal kerja wajib diisi.',
            'date.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'date.unique' => 'Shift kerja untuk tanggal ini sudah pernah dibuka.',
            'work_date.required' => 'Tanggal kerja wajib diisi.',
            'work_date.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'work_date.unique' => 'Shift kerja untuk tanggal ini sudah pernah dibuka.',
        ];
    }
}
