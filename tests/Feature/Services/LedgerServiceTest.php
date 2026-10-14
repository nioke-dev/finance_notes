<?php

namespace Tests\Feature\Services;

use App\Enums\AccountCategory;
use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Exceptions\UnbalancedLedgerException;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\WorkDay;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledgerService = new LedgerService();
    }

    public function test_record_transaction_persists_records_and_updates_account_balances_correctly(): void
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
            'name' => 'BCA Syariah',
            'type' => AccountType::BANK,
            'initial_balance' => 200000,
            'current_balance' => 200000,
            'is_active' => true,
        ]);

        // Tarik tunai ATM BCA 100.000 ke Kas: Debit Kas 100k, Kredit BCA 100k
        $entries = [
            [
                'account_id' => $cash->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => 100000,
            ],
            [
                'account_id' => $bca->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => 100000,
            ],
        ];

        $tx = $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'transfer',
            description: 'Tarik tunai ATM BCA',
            transactionTime: now(),
            entries: $entries,
            isEstimatedTime: false,
            metadata: ['atm' => 'Kawi Malang']
        );

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertCount(2, $tx->ledgerEntries);

        $cash->refresh();
        $bca->refresh();

        // Saldo Kas bertambah (+100.000 -> 150.000)
        $this->assertSame(150000, $cash->current_balance);
        // Saldo BCA berkurang (-100.000 -> 100.000)
        $this->assertSame(100000, $bca->current_balance);
    }

    public function test_income_and_expense_balances_update_correctly(): void
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

        $expenseFuel = Account::create([
            'name' => 'Beban Bensin',
            'type' => AccountType::EXPENSE,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        // Beli Bensin: Debit Beban 15.000, Kredit Kas 15.000
        $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: 'Beli Pertalite SPBU',
            transactionTime: now(),
            entries: [
                ['account_id' => $expenseFuel->id, 'entry_type' => EntryType::DEBIT, 'amount' => 15000],
                ['account_id' => $cash->id, 'entry_type' => EntryType::CREDIT, 'amount' => 15000],
            ]
        );

        $cash->refresh();
        $expenseFuel->refresh();

        $this->assertSame(35000, $cash->current_balance);
        $this->assertSame(15000, $expenseFuel->current_balance);
    }

    public function test_unbalanced_entries_throw_exception_and_rollback_cleanly(): void
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
            'name' => 'BCA Syariah',
            'type' => AccountType::BANK,
            'initial_balance' => 100000,
            'current_balance' => 100000,
            'is_active' => true,
        ]);

        $unbalancedEntries = [
            ['account_id' => $cash->id, 'entry_type' => EntryType::DEBIT, 'amount' => 50000],
            ['account_id' => $bca->id, 'entry_type' => EntryType::CREDIT, 'amount' => 40000],
        ];

        try {
            $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'transfer',
                description: 'Transfer Gagal',
                transactionTime: now(),
                entries: $unbalancedEntries
            );
            $this->fail('Harusnya melempar UnbalancedLedgerException');
        } catch (UnbalancedLedgerException $e) {
            $this->assertSame(10000, $e->getDiscrepancy());
        }

        // Pastikan zero database mutation (tidak ada transaksi & ledger entry tercipta)
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('ledger_entries', 0);

        // Pastikan saldo tetap utuh
        $cash->refresh();
        $bca->refresh();
        $this->assertSame(50000, $cash->current_balance);
        $this->assertSame(100000, $bca->current_balance);
    }
}
