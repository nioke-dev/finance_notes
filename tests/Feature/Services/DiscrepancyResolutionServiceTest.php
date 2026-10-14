<?php

namespace Tests\Feature\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\WorkDay;
use App\Services\DiscrepancyResolutionService;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DiscrepancyResolutionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DiscrepancyResolutionService $service;
    protected Account $cashAccount;
    protected Account $foodExpenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DiscrepancyResolutionService::class);

        $this->cashAccount = Account::create([
            'name' => 'Cash',
            'type' => AccountType::CASH,
            'current_balance' => 100000,
            'initial_balance' => 100000,
        ]);

        $this->foodExpenseAccount = Account::create([
            'name' => 'Beban Makan & Minum Driver',
            'type' => AccountType::EXPENSE,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);
    }

    public function test_adding_forgotten_expense_to_past_work_day_sets_placeholder_and_logs_audit(): void
    {
        $pastDate = now()->subDays(2)->toDateString();
        $pastWorkDay = WorkDay::create([
            'work_date' => $pastDate,
            'start_time' => '08:00:00',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $transaction = $this->service->addForgottenExpense(
            pastWorkDay: $pastWorkDay,
            sourceAccount: $this->cashAccount,
            expenseAccount: $this->foodExpenseAccount,
            amount: 15000,
            description: 'Kopi dan rokok warung malam',
            reason: 'Lupa catat saat lelah semalam',
            usePlaceholderTime: true
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertTrue($transaction->is_estimated_time);
        $this->assertEquals('23:59:00', $transaction->transaction_time->format('H:i:s'));
        $this->assertEquals($pastDate, $transaction->transaction_time->format('Y-m-d'));

        // Saldo kas terpotong 15.000
        $this->cashAccount->refresh();
        $this->assertEquals(85000, $this->cashAccount->current_balance);

        // Audit log tercatat
        $auditLog = AuditLog::where('auditable_type', Transaction::class)
            ->where('auditable_id', $transaction->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertStringContainsString('Lupa catat saat lelah semalam', $auditLog->reason);
    }

    public function test_cannot_add_transaction_to_already_finalized_work_day(): void
    {
        $pastDate = now()->subDays(2)->toDateString();
        $finalizedWorkDay = WorkDay::create([
            'work_date' => $pastDate,
            'start_time' => '08:00:00',
            'status' => WorkDay::STATUS_FINAL,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hari kerja ini sudah final');

        $this->service->addForgottenExpense(
            pastWorkDay: $finalizedWorkDay,
            sourceAccount: $this->cashAccount,
            expenseAccount: $this->foodExpenseAccount,
            amount: 15000,
            description: 'Kopi warung',
            reason: 'Lupa input saat istirahat'
        );
    }

    public function test_adjust_unresolved_cash_shortage_reduces_cash_and_logs_audit(): void
    {
        $workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        // Discrepancy -2000 (uang fisik di dompet kurang Rp2.000)
        $tx = $this->service->adjustUnresolvedCashDiscrepancy(
            workDay: $workDay,
            cashAccount: $this->cashAccount,
            discrepancy: -2000,
            reason: 'Uang receh Rp2.000 tercecer di jalan saat bayar parkir'
        );

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->cashAccount->refresh();
        $this->assertEquals(98000, $this->cashAccount->current_balance);

        $auditLog = AuditLog::where('action', 'adjust_discrepancy')->first();
        $this->assertNotNull($auditLog);
        $this->assertStringContainsString('tercecer di jalan', $auditLog->reason);
    }

    public function test_adjust_unresolved_cash_surplus_increases_cash_and_logs_audit(): void
    {
        $workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        // Discrepancy +3000 (uang fisik di dompet lebih Rp3.000)
        $tx = $this->service->adjustUnresolvedCashDiscrepancy(
            workDay: $workDay,
            cashAccount: $this->cashAccount,
            discrepancy: 3000,
            reason: 'Kembalian warung lebih Rp3.000 tidak diketahui'
        );

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->cashAccount->refresh();
        $this->assertEquals(103000, $this->cashAccount->current_balance);
    }

    public function test_adjust_unresolved_discrepancy_fails_if_reason_too_short(): void
    {
        $workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alasan penyesuaian selisih kas wajib diisi minimal 5 karakter');

        $this->service->adjustUnresolvedCashDiscrepancy(
            workDay: $workDay,
            cashAccount: $this->cashAccount,
            discrepancy: -1000,
            reason: 'lupa'
        );
    }
}
