<?php

namespace Tests\Feature\Api;

use App\Models\VoiceMemo;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ListVoiceMemosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_list_of_voice_memos_ordered_by_newest(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $memo1 = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => 'voice_memos/memo_old.m4a',
            'duration_seconds' => 10,
            'recorded_at' => Carbon::parse('2026-10-14 10:00:00'),
        ]);

        $memo2 = VoiceMemo::create([
            'work_day_id' => $workDay->id,
            'file_path' => 'voice_memos/memo_new.m4a',
            'duration_seconds' => 25,
            'recorded_at' => Carbon::parse('2026-10-14 12:00:00'),
        ]);

        $response = $this->getJson("/api/v1/voice-memos?work_day_id={$workDay->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals($memo2->id, $data[0]['id']); // Newest first
        $this->assertEquals($memo1->id, $data[1]['id']); // Oldest second
    }

    public function test_returns_empty_array_when_no_memos_for_work_day(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/voice-memos?work_day_id={$workDay->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [],
            ]);
    }

    public function test_fails_validation_if_work_day_id_missing(): void
    {
        $response = $this->getJson('/api/v1/voice-memos');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['work_day_id']);
    }
}
