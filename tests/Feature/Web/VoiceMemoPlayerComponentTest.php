<?php

namespace Tests\Feature\Web;

use App\Models\VoiceMemo;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceMemoPlayerComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_memo_player_renders_audio_source_and_timestamp(): void
    {
        Storage::fake('public');

        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $memo = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => 'voice-memos/memo-1.mp4',
            'duration_seconds' => 18,
            'recorded_at' => Carbon::parse('2026-10-14 14:25:00'),
        ]);

        $renderedHtml = Blade::render(
            '<x-voice-memo-player :memo="$memo" />',
            ['memo' => $memo]
        );

        $this->assertStringContainsString('Rekaman: 14:25 WIB', $renderedHtml);
        $this->assertStringContainsString('18s', $renderedHtml);
        $this->assertStringContainsString('voice-memos/memo-1.mp4', $renderedHtml);
        $this->assertStringContainsString('<audio controls', $renderedHtml);
    }

    public function test_voice_memo_player_renders_gps_link_when_coordinates_present(): void
    {
        Storage::fake('public');

        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $memo = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => 'voice-memos/memo-2.mp4',
            'duration_seconds' => 30,
            'latitude' => -7.9826,
            'longitude' => 112.6308,
            'recorded_at' => Carbon::parse('2026-10-14 16:10:00'),
        ]);

        $renderedHtml = Blade::render(
            '<x-voice-memo-player :memo="$memo" />',
            ['memo' => $memo]
        );

        $this->assertStringContainsString('https://www.google.com/maps?q=-7.9826,112.6308', $renderedHtml);
        $this->assertStringContainsString('GPS', $renderedHtml);
    }
}
