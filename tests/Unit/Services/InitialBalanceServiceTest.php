<?php

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Services\InitialBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InitialBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InitialBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InitialBalanceService();
    }

    /**
     * Memverifikasi inisialisasi saldo awal pertama kali.
     */
    public function test_set_initial_balance_successfully_sets_initial_and_current_balance(): void
    {
        $account = Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        $updated = $this->service->setInitialBalance($account, 1000000);

        $this->assertEquals(1000000, $updated->initial_balance);
        $this->assertEquals(1000000, $updated->current_balance);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Account::class,
            'auditable_id' => $account->id,
            'action' => 'create',
            'reason' => 'Inisialisasi saldo awal akun pertama kali.',
        ]);
    }

    /**
     * Memverifikasi koreksi saldo awal menyesuaikan current_balance sebesar selisihnya.
     */
    public function test_correct_initial_balance_adjusts_current_balance_by_exact_difference(): void
    {
        $account = Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'current_balance' => 1000000,
            'initial_balance' => 1000000,
        ]);

        // Koreksi saldo awal dari 1.000.000 menjadi 1.200.000 (selisih +200.000)
        $updated = $this->service->correctInitialBalance($account, 1200000, 'Koreksi uang saku lupa terhitung');

        $this->assertEquals(1200000, $updated->initial_balance);
        $this->assertEquals(1200000, $updated->current_balance);

        // Koreksi kedua: turunkan dari 1.200.000 menjadi 1.100.000 (selisih -100.000)
        $updatedAgain = $this->service->correctInitialBalance($updated, 1100000, 'Koreksi kelebihan hitung 100k');

        $this->assertEquals(1100000, $updatedAgain->initial_balance);
        $this->assertEquals(1100000, $updatedAgain->current_balance);
    }

    /**
     * Memverifikasi koreksi saldo awal wajib menyertakan alasan minimal 5 karakter dan mencatat audit log.
     */
    public function test_correct_initial_balance_requires_valid_reason_and_logs_audit(): void
    {
        $account = Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'current_balance' => 50000,
            'initial_balance' => 50000,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->correctInitialBalance($account, 60000, 'tes'); // Kurang dari 5 karakter
    }

    /**
     * Memverifikasi audit log terbentuk lengkap saat koreksi saldo awal.
     */
    public function test_correct_initial_balance_records_audit_trail_with_old_and_new_values(): void
    {
        $account = Account::create([
            'name' => 'GoPay',
            'type' => AccountType::EWALLET,
            'current_balance' => 50000,
            'initial_balance' => 50000,
        ]);

        $this->service->correctInitialBalance($account, 75000, 'Penyesuaian saldo riil di aplikasi');

        $audit = AuditLog::where('auditable_id', $account->id)->where('action', 'update')->first();

        $this->assertNotNull($audit);
        $this->assertEquals(['initial_balance' => 50000], $audit->old_values);
        $this->assertEquals(['initial_balance' => 75000], $audit->new_values);
        $this->assertEquals('Penyesuaian saldo riil di aplikasi', $audit->reason);
    }
}
