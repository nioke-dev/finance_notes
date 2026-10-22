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
        $targetDate = $validated['work_date'] ?? null;
        $workDayId = $validated['work_day_id'] ?? null;

        if (!empty($targetDate)) {
            $workDay = WorkDay::firstOrCreate(
                ['work_date' => $targetDate],
                [
                    'day_type' => WorkDay::TYPE_ON_BID,
                    'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
                    'opened_at' => \Illuminate\Support\Carbon::parse($targetDate . ' 08:00:00'),
                ]
            );
        } elseif (!empty($workDayId)) {
            $workDay = WorkDay::findOrFail($workDayId);
        } else {
            $workDay = WorkDay::firstOrCreate(
                ['work_date' => now()->toDateString()],
                [
                    'day_type' => WorkDay::TYPE_ON_BID,
                    'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
                    'opened_at' => now(),
                ]
            );
        }

        $file = $request->file('audio_file');
        $fileName = sprintf(
            'memo_%d_%s_%s.%s',
            $workDay->id,
            now()->format('Ymd_His'),
            uniqid(),
            $file->getClientOriginalExtension()
        );

        $path = $file->storeAs('voice_memos', $fileName, 'public');

        $recordedAt = !empty($validated['recorded_at'])
            ? \Illuminate\Support\Carbon::parse($validated['recorded_at'])
            : (!empty($targetDate) && $targetDate !== now()->toDateString()
                ? \Illuminate\Support\Carbon::parse($targetDate . ' ' . now()->format('H:i:s'))
                : now());

        $memo = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => $path,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'recorded_at' => $recordedAt,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Voice memo berhasil disimpan.',
            'data' => [
                'memo_id' => $memo->id,
                'audio_url' => $memo->audio_url,
                'duration_seconds' => $memo->duration_seconds,
                'recorded_at' => $memo->recorded_at->toISOString(),
                'work_date' => $workDay->work_date instanceof \Carbon\CarbonInterface ? $workDay->work_date->toDateString() : substr((string) $workDay->work_date, 0, 10),
                'date_formatted' => $memo->recorded_at->format('d M Y'),
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

    /**
     * Stream audio voice memo dengan dukungan byte-range dan MIME type akurat
     */
    public function stream(VoiceMemo $voiceMemo): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filePath = $voiceMemo->file_path;
        $fullPath = null;

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($filePath);
        } elseif (file_exists(storage_path('app/public/' . $filePath))) {
            $fullPath = storage_path('app/public/' . $filePath);
        } elseif (file_exists(public_path('storage/' . $filePath))) {
            $fullPath = public_path('storage/' . $filePath);
        } elseif (file_exists(storage_path('app/' . $filePath))) {
            $fullPath = storage_path('app/' . $filePath);
        }

        if (!$fullPath || !file_exists($fullPath)) {
            abort(404, 'File audio tidak ditemukan di server.');
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = match ($ext) {
            'webm' => 'audio/webm',
            'ogg' => 'audio/ogg',
            'mp4', 'm4a' => 'audio/mp4',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            default => 'audio/webm',
        };

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
