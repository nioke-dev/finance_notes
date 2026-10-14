<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi penyimpanan array JSON pada old_values dan new_values.
     */
    public function test_audit_log_persists_old_and_new_json_payloads_as_arrays(): void
    {
        $log = AuditLog::create([
            'auditable_type' => 'App\Models\Account',
            'auditable_id' => 1,
            'action' => 'update',
            'old_values' => ['initial_balance' => 100000],
            'new_values' => ['initial_balance' => 150000],
            'reason' => 'Koreksi salah input saat setup.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'update',
            'reason' => 'Koreksi salah input saat setup.',
        ]);

        $retrieved = AuditLog::find($log->id);
        $this->assertIsArray($retrieved->old_values);
        $this->assertIsArray($retrieved->new_values);
        $this->assertEquals(100000, $retrieved->old_values['initial_balance']);
        $this->assertEquals(150000, $retrieved->new_values['initial_balance']);
        $this->assertNotNull($retrieved->created_at);
    }

    /**
     * Memverifikasi kolom reason wajib diisi.
     */
    public function test_audit_log_requires_reason_to_be_present(): void
    {
        $this->expectException(QueryException::class);

        AuditLog::create([
            'auditable_type' => 'App\Models\Account',
            'auditable_id' => 1,
            'action' => 'create',
            // reason is omitted
        ]);
    }

    /**
     * Memverifikasi tabel audit_logs bersifat append-only tanpa kolom updated_at.
     */
    public function test_audit_log_is_append_only_without_updated_at_column(): void
    {
        $this->assertTrue(Schema::hasColumn('audit_logs', 'created_at'));
        $this->assertFalse(Schema::hasColumn('audit_logs', 'updated_at'));
    }
}
