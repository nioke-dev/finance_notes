<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVoiceMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'audio_file' => ['required', 'file', 'mimes:m4a,mp3,wav,aac,ogg,webm,mp4', 'max:10240'], // Max 10MB
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
