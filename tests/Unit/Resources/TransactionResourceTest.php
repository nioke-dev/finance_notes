<?php

namespace Tests\Unit\Resources;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TransactionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_resource_renders_expected_keys_and_integer_amount(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::create([
            'name' => 'Kas Tunai Dompet',
            'type' => AccountType::CASH,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $fuel = Account::create([
            'name' => 'Beban Bensin',
            'type' => AccountType::EXPENSE,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'expense',
            'description' => 'Beli Pertalite',
            'transaction_time' => '2026-10-14 14:25:00',
            'is_estimated_time' => false,
            'metadata' => ['liters' => 2],
        ]);

        LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $fuel->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => 20000,
            'created_at' => now(),
        ]);

        LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $cash->id,
            'entry_type' => EntryType::CREDIT,
            'amount' => 20000,
            'created_at' => now(),
        ]);

        $transaction->load('ledgerEntries');

        $resource = new TransactionResource($transaction);
        $result = $resource->toArray(Request::create('/api/v1/transactions'));

        $this->assertSame($transaction->id, $result['id']);
        $this->assertStringStartsWith('TX-', $result['reference_number']);
        $this->assertSame('14:25 WIB', $result['formatted_time']);
        $this->assertIsInt($result['amount']);
        $this->assertSame(20000, $result['amount']);
        $this->assertFalse($result['is_reversal']);
        $this->assertSame('Beli Pertalite', $result['description']);
        $this->assertCount(2, $result['ledger_entries']);
    }

    public function test_reversal_transaction_marks_is_reversal_true(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'reversal',
            'description' => 'Pembalikan Transaksi #1',
            'transaction_time' => now(),
            'metadata' => ['reversed_transaction_id' => 1],
        ]);

        $resource = new TransactionResource($transaction);
        $result = $resource->toArray(Request::create('/api/v1/transactions'));

        $this->assertTrue($result['is_reversal']);
        $this->assertSame('reversal', $result['type']);
    }
}
