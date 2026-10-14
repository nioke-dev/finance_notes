<?php

namespace Tests\Unit\Models;

use App\Models\VoiceMemo;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VoiceMemoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_memo_belongs_to_work_day(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $memo = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => 'voice_memos/memo_001.m4a',
            'duration_seconds' => 20,
            'latitude' => -7.96662000,
            'longitude' => 112.63263000,
            'transcription_text' => 'Parkir gratis dekat toko helm',
            'recorded_at' => Carbon::parse('2026-10-14 14:30:00'),
        ]);

        $this->assertInstanceOf(WorkDay::class, $memo->workDay);
        $this->assertEquals($workDay->id, $memo->workDay->id);
        $this->assertTrue($workDay->voiceMemos->contains($memo));
    }

    public function test_audio_url_accessor_generates_valid_storage_link(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $memo = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => 'voice_memos/intercom_recording.m4a',
            'duration_seconds' => 12,
            'recorded_at' => now(),
        ]);

        $audioUrl = $memo->audio_url;

        $this->assertNotNull($audioUrl);
        $this->assertStringContainsString('voice_memos/intercom_recording.m4a', $audioUrl);
        $this->assertStringContainsString('/storage/', $audioUrl);
    }
}
