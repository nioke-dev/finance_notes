<?php

namespace Tests\Feature\Web;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDescriptionEditWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WorkDay $workDay;
    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DefaultAccountsSeeder::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $this->cashAccount->update([
            'current_balance' => 100000,
            'initial_balance' => 100000,
        ]);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
            'day_type' => WorkDay::TYPE_ON_BID,
            'opened_at' => now(),
        ]);
    }

    public function test_user_can_edit_transaction_description_and_create_audit_log(): void
    {
        // 1. Buat transaksi dengan salah ketik (typo "brli")
        $tx = Transaction::create([
            'work_day_id' => $this->workDay->id,
            'type' => 'expense',
            'description' => 'brli bensin motor pertalite',
            'transaction_time' => now(),
            'is_estimated_time' => false,
        ]);

        // 2. Kirim request edit description menjadi "beli bensin motor pertalite"
        $response = $this->post("/operational/transactions/{$tx->id}/edit-description", [
            'description' => 'beli bensin motor pertalite',
            'reason' => 'Perbaiki salah ketik dari brli jadi beli',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 3. Verifikasi deskripsi transaksi terupdate
        $this->assertEquals('beli bensin motor pertalite', $tx->fresh()->description);

        // 4. Verifikasi audit log tersimpan
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Transaction::class,
            'auditable_id' => $tx->id,
            'action' => 'transaction_description_updated',
            'reason' => 'Perbaiki salah ketik dari brli jadi beli',
        ]);

        $auditLog = AuditLog::where('auditable_id', $tx->id)->latest('id')->firstOrFail();
        $this->assertEquals('brli bensin motor pertalite', $auditLog->old_values['description']);
        $this->assertEquals('beli bensin motor pertalite', $auditLog->new_values['description']);

        // 5. Verifikasi di dashboard muncul nama baru dan tombol edit
        $dashResponse = $this->get('/dashboard');
        $dashResponse->assertOk();
        $dashResponse->assertSee('beli bensin motor pertalite');
        $dashResponse->assertSee('Edit');
    }

    public function test_user_cannot_edit_transaction_with_empty_description(): void
    {
        $tx = Transaction::create([
            'work_day_id' => $this->workDay->id,
            'type' => 'expense',
            'description' => 'beli es teh',
            'transaction_time' => now(),
            'is_estimated_time' => false,
        ]);

        $response = $this->post("/operational/transactions/{$tx->id}/edit-description", [
            'description' => '',
        ]);

        $response->assertSessionHasErrors('description');
        $this->assertEquals('beli es teh', $tx->fresh()->description);
    }
}
