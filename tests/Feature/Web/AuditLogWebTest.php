<?php

namespace Tests\Feature\Web;

use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_page_renders_chronological_logs(): void
    {
        AuditLog::create([
            'action' => 'reversal',
            'auditable_type' => Transaction::class,
            'auditable_id' => 99,
            'reason' => 'Salah input nominal parkir',
            'old_values' => ['amount' => 5000],
            'new_values' => ['amount' => 2000],
            'created_at' => now(),
        ]);

        $response = $this->get('/audit-logs');

        $response->assertStatus(200);
        $response->assertViewIs('pages.audit_logs.index');
        $response->assertSee('Audit Log Explorer');
        $response->assertSee('reversal');
        $response->assertSee('Salah input nominal parkir');
        $response->assertSee('Transaction #99');
    }
}
