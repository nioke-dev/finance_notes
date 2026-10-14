<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_belongs_to_work_day(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'income',
            'description' => 'Pendapatan Bersih ShopeeFood',
            'transaction_time' => now(),
            'is_estimated_time' => false,
            'metadata' => ['order_count' => 15],
        ]);

        $this->assertInstanceOf(WorkDay::class, $transaction->workDay);
        $this->assertSame($workDay->id, $transaction->workDay->id);
    }

    public function test_transaction_has_many_ledger_entries(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'income',
            'description' => 'Pendapatan Bersih ShopeeFood',
            'transaction_time' => now(),
        ]);

        // Verifikasi relasi ledgerEntries menghasilkan relasi HasMany
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $transaction->ledgerEntries());
        $this->assertSame('transaction_id', $transaction->ledgerEntries()->getForeignKeyName());
    }

    public function test_metadata_casts_to_array_correctly(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $meta = [
            'lat' => -7.96,
            'long' => 112.63,
            'resto' => 'Ayam Goreng Nelongso',
        ];

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'expense',
            'description' => 'Parkir Resto',
            'transaction_time' => '2026-10-14 13:00:00',
            'is_estimated_time' => false,
            'metadata' => $meta,
        ]);

        $this->assertIsArray($transaction->metadata);
        $this->assertSame(-7.96, $transaction->metadata['lat']);
        $this->assertSame('Ayam Goreng Nelongso', $transaction->metadata['resto']);
        $this->assertInstanceOf(Carbon::class, $transaction->transaction_time);
        $this->assertFalse($transaction->is_estimated_time);
    }

    public function test_transaction_scopes_filter_by_type(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'income',
            'description' => 'Income 1',
            'transaction_time' => now(),
        ]);

        Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'expense',
            'description' => 'Expense 1',
            'transaction_time' => now(),
        ]);

        Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'transfer',
            'description' => 'Transfer 1',
            'transaction_time' => now(),
        ]);

        $this->assertCount(1, Transaction::income()->get());
        $this->assertCount(1, Transaction::expense()->get());
        $this->assertCount(1, Transaction::transfer()->get());
    }
}
