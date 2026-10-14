<?php

namespace Tests\Unit\Migrations;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkDaysTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi kolom yang diharapkan pada tabel work_days.
     */
    public function test_work_days_table_has_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('work_days'));

        $expectedColumns = [
            'id',
            'work_date',
            'status',
            'opened_at',
            'closed_at',
            'total_orders',
            'online_hours',
            'is_performance_eligible',
            'closing_notes',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('work_days', $column),
                "Kolom {$column} tidak ditemukan di tabel work_days."
            );
        }
    }

    /**
     * Memverifikasi tanggal kerja bersifat unik (mencegah duplikasi work_date).
     */
    public function test_work_date_must_be_unique(): void
    {
        DB::table('work_days')->insert([
            'work_date' => '2026-10-14',
            'status' => 'needs_reconciliation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('work_days')->insert([
            'work_date' => '2026-10-14',
            'status' => 'needs_reconciliation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Memverifikasi status bawaan adalah needs_reconciliation.
     */
    public function test_default_status_is_needs_reconciliation(): void
    {
        $id = DB::table('work_days')->insertGetId([
            'work_date' => '2026-10-15',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('work_days')->where('id', $id)->first();
        $this->assertEquals('needs_reconciliation', $row->status);
    }
}
