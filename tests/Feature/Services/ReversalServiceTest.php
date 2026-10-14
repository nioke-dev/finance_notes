<?php

namespace Tests\Feature\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\WorkDay;
use App\Services\LedgerService;
use App\Services\ReversalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReversalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $ledgerService;
    protected ReversalService $reversalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledgerService = new LedgerService();
        $this->reversalService = new ReversalService($this->ledgerService);
    }

    public function test_reversal_restores_account_balances_to_exact_previous_state(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::create([
            'name' => 'Kas Tunai Dompet',
            'type' => AccountType::CASH,
            'initial_balance' => 100000,
            'current_balance' => 100000,
            'is_active' => true,
        ]);

        $fuelExpense = Account::create([
            'name' => 'Beban Bensin',
            'type' => AccountType::EXPENSE,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        // Catat pengeluaran bensin 30.000: Debit Beban 30k, Kredit Kas 30k
        $originalTx = $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: 'Beli Pertalite SPBU',
            transactionTime: now(),
            entries: [
                ['account_id' => $fuelExpense->id, 'entry_type' => EntryType::DEBIT, 'amount' => 30000],
                ['account_id' => $cash->id, 'entry_type' => EntryType::CREDIT, 'amount' => 30000],
            ]
        );

        $cash->refresh();
        $fuelExpense->refresh();
        $this->assertSame(70000, $cash->current_balance);
        $this->assertSame(30000, $fuelExpense->current_balance);

        // Eksekusi reversal
        $reversalTx = $this->reversalService->reverseTransaction(
            originalTransaction: $originalTx,
            reason: 'Salah pencatatan, harusnya dibayar pakai QRIS bukan kas'
        );

        $this->assertInstanceOf(Transaction::class, $reversalTx);
        $this->assertSame('reversal', $reversalTx->type);

        $cash->refresh();
        $fuelExpense->refresh();

        // Saldo kembali persis semula
        $this->assertSame(100000, $cash->current_balance);
        $this->assertSame(0, $fuelExpense->current_balance);

        // Transaksi asal tetap ada di database (zero hard delete)
        $this->assertDatabaseHas('transactions', ['id' => $originalTx->id]);
    }

    public function test_cannot_reverse_same_transaction_twice(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::create([
            'name' => 'Kas Tunai Dompet',
            'type' => AccountType::CASH,
            'initial_balance' => 50000,
            'current_balance' => 50000,
            'is_active' => true,
        ]);

        $bca = Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'initial_balance' => 100000,
            'current_balance' => 100000,
            'is_active' => true,
        ]);

        $tx = $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'transfer',
            description: 'Tarik Tunai',
            transactionTime: now(),
            entries: [
                ['account_id' => $cash->id, 'entry_type' => EntryType::DEBIT, 'amount' => 20000],
                ['account_id' => $bca->id, 'entry_type' => EntryType::CREDIT, 'amount' => 20000],
            ]
        );

        $this->reversalService->reverseTransaction($tx, 'Alasan pertama valid');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaksi ini sudah pernah dibatalkan sebelumnya.');

        $this->reversalService->reverseTransaction($tx, 'Mencoba membatalkan kedua kali');
    }

    public function test_reversal_requires_reason_and_creates_audit_log(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::create([
            'name' => 'Kas Tunai Dompet',
            'type' => AccountType::CASH,
            'initial_balance' => 50000,
            'current_balance' => 50000,
            'is_active' => true,
        ]);

        $expense = Account::create([
            'name' => 'Beban Parkir',
            'type' => AccountType::EXPENSE,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $tx = $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: 'Parkir Indomaret',
            transactionTime: now(),
            entries: [
                ['account_id' => $expense->id, 'entry_type' => EntryType::DEBIT, 'amount' => 2000],
                ['account_id' => $cash->id, 'entry_type' => EntryType::CREDIT, 'amount' => 2000],
            ]
        );

        // Reason < 5 karakter
        try {
            $this->reversalService->reverseTransaction($tx, 'abc');
            $this->fail('Harusnya melempar InvalidArgumentException karena reason terlalu pendek');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('minimal 5 karakter', $e->getMessage());
        }

        // Reason valid
        $this->reversalService->reverseTransaction($tx, 'Batal parkir karena tukang parkir tidak ada');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Transaction::class,
            'auditable_id' => $tx->id,
            'action' => 'reversal',
            'reason' => 'Batal parkir karena tukang parkir tidak ada',
        ]);
    }
}
