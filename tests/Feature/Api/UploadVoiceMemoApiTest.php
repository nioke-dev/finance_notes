<?php

namespace Tests\Feature\Api;

use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadVoiceMemoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_voice_memo_audio_file(): void
    {
        Storage::fake('public');

        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $fakeAudio = UploadedFile::fake()->create('intercom_audio.m4a', 500, 'audio/mp4');

        $payload = [
            'work_day_id' => $workDay->id,
            'audio_file' => $fakeAudio,
            'duration_seconds' => 18,
            'latitude' => -7.983908,
            'longitude' => 112.621391,
        ];

        $response = $this->postJson('/api/v1/quick/voice-memo', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Voice memo berhasil disimpan.',
                'data' => [
                    'duration_seconds' => 18,
                ],
            ]);

        $this->assertDatabaseHas('voice_memos', [
            'work_day_id' => $workDay->id,
            'duration_seconds' => 18,
            'latitude' => -7.983908,
            'longitude' => 112.621391,
        ]);

        // Verifikasi file audio tersimpan di disk public
        $storedMemos = Storage::disk('public')->files('voice_memos');
        $this->assertNotEmpty($storedMemos);
    }

    public function test_upload_fails_for_non_audio_files(): void
    {
        Storage::fake('public');

        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $fakePdf = UploadedFile::fake()->create('document.pdf', 200, 'application/pdf');

        $payload = [
            'work_day_id' => $workDay->id,
            'audio_file' => $fakePdf,
        ];

        $response = $this->postJson('/api/v1/quick/voice-memo', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['audio_file']);
    }
}
