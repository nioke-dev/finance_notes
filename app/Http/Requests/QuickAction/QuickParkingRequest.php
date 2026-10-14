<?php

namespace App\Http\Requests\QuickAction;

use Illuminate\Foundation\Http\FormRequest;

class QuickParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'amount' => ['nullable', 'integer', 'in:1000,2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'restaurant_name' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.in' => 'Nominal parkir cepat hanya diperbolehkan Rp1.000 atau Rp2.000.',
            'latitude.between' => 'Format latitude GPS tidak valid.',
            'longitude.between' => 'Format longitude GPS tidak valid.',
        ];
    }
}
