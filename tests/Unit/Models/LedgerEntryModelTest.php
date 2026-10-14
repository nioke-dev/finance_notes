<?php

namespace Tests\Unit\Models;

use App\Enums\AccountCategory;
use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LedgerEntryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_entry_casts_entry_type_to_enum(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $account = Account::create([
            'name' => 'Kas Tunai Dompet',
            'type' => AccountType::CASH,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'expense',
            'description' => 'Beli bensin',
            'transaction_time' => now(),
        ]);

        $entry = LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'entry_type' => 'debit',
            'amount' => 15000,
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(EntryType::class, $entry->entry_type);
        $this->assertSame(EntryType::DEBIT, $entry->entry_type);
        $this->assertInstanceOf(Carbon::class, $entry->created_at);
    }

    public function test_ledger_entry_amount_is_strictly_cast_to_integer(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $account = Account::create([
            'name' => 'BCA Syariah',
            'type' => AccountType::BANK,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'income',
            'description' => 'Top Up Modal',
            'transaction_time' => now(),
        ]);

        $entry = LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => 50000,
            'created_at' => now(),
        ]);

        $this->assertIsInt($entry->amount);
        $this->assertSame(50000, $entry->amount);
    }

    public function test_scope_debits_and_credits_filter_correctly(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $accCash = Account::create([
            'name' => 'Kas Tunai Dompet',
            'type' => AccountType::CASH,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $accBca = Account::create([
            'name' => 'BCA Syariah',
            'type' => AccountType::BANK,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'transfer',
            'description' => 'Tarik Tunai',
            'transaction_time' => now(),
        ]);

        // Debit Kas 50k, Kredit BCA 50k
        LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $accCash->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => 50000,
            'created_at' => now(),
        ]);

        LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $accBca->id,
            'entry_type' => EntryType::CREDIT,
            'amount' => 50000,
            'created_at' => now(),
        ]);

        $debits = LedgerEntry::debits()->get();
        $credits = LedgerEntry::credits()->get();

        $this->assertCount(1, $debits);
        $this->assertCount(1, $credits);
        $this->assertSame($accCash->id, $debits->first()->account_id);
        $this->assertSame($accBca->id, $credits->first()->account_id);
    }

    public function test_ledger_entry_relations(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $account = Account::create([
            'name' => 'Kas Tunai Dompet',
            'type' => AccountType::CASH,
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'expense',
            'description' => 'Beli es teh',
            'transaction_time' => now(),
        ]);

        $entry = LedgerEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'entry_type' => EntryType::CREDIT,
            'amount' => 5000,
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(Transaction::class, $entry->transaction);
        $this->assertSame($transaction->id, $entry->transaction->id);
        $this->assertInstanceOf(Account::class, $entry->account);
        $this->assertSame($account->id, $entry->account->id);
    }
}
