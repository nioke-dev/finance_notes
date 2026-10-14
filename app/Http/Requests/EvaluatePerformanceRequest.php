<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluatePerformanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'online_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'peak_hour_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'completion_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'eligible_orders_count' => ['required', 'integer', 'min:0'],
        ];
    }
}
