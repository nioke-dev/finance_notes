<?php

namespace Tests\Unit\Migrations;

use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VoiceMemosTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_memos_table_has_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('voice_memos'));

        $expectedColumns = [
            'id',
            'work_day_id',
            'file_path',
            'duration_seconds',
            'latitude',
            'longitude',
            'transcription_text',
            'recorded_at',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('voice_memos', $column),
                "Column {$column} does not exist in voice_memos table."
            );
        }
    }

    public function test_latitude_and_longitude_precision(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $id = DB::table('voice_memos')->insertGetId([
            'work_day_id' => $workDay->id,
            'file_path' => 'voice_memos/test_memo_001.m4a',
            'duration_seconds' => 15,
            'latitude' => -7.96662055,
            'longitude' => 112.63263211,
            'transcription_text' => 'Parkir gratis dekat minimarket Sutoyo',
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $record = DB::table('voice_memos')->where('id', $id)->first();

        $this->assertEquals(-7.96662055, (float) $record->latitude);
        $this->assertEquals(112.63263211, (float) $record->longitude);
        $this->assertEquals('voice_memos/test_memo_001.m4a', $record->file_path);
        $this->assertEquals(15, $record->duration_seconds);
    }
}
