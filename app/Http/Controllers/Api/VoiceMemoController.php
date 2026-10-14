<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadVoiceMemoRequest;
use App\Models\VoiceMemo;
use App\Models\WorkDay;
use Illuminate\Http\JsonResponse;

class VoiceMemoController extends Controller
{
    public function upload(UploadVoiceMemoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $file = $request->file('audio_file');
        $fileName = sprintf(
            'memo_%d_%s_%s.%s',
            $workDay->id,
            now()->format('Ymd_His'),
            uniqid(),
            $file->getClientOriginalExtension()
        );

        $path = $file->storeAs('voice_memos', $fileName, 'public');

        $memo = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => $path,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Voice memo berhasil disimpan.',
            'data' => [
                'memo_id' => $memo->id,
                'audio_url' => $memo->audio_url,
                'duration_seconds' => $memo->duration_seconds,
                'recorded_at' => $memo->recorded_at->toISOString(),
            ],
        ], 201);
    }

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
        ]);

        $memos = VoiceMemo::where('work_day_id', $request->query('work_day_id'))
            ->orderBy('recorded_at', 'desc')
            ->get()
            ->map(fn (VoiceMemo $memo) => [
                'id' => $memo->id,
                'audio_url' => $memo->audio_url,
                'duration_seconds' => $memo->duration_seconds,
                'latitude' => $memo->latitude ? (float) $memo->latitude : null,
                'longitude' => $memo->longitude ? (float) $memo->longitude : null,
                'recorded_at' => $memo->recorded_at->toISOString(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $memos,
        ], 200);
    }
}
