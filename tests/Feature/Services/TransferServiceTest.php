<?php

namespace Tests\Feature\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use App\Services\LedgerService;
use App\Services\TransferService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransferService $transferService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->transferService = new TransferService(new LedgerService());
    }

    public function test_transfer_without_admin_fee_moves_exact_balance_between_accounts(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $bca = Account::where('name', 'BCA')->firstOrFail();
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();

        $bca->update(['current_balance' => 200000]);
        $shopeePay->update(['current_balance' => 10000]);

        $tx = $this->transferService->transfer(
            workDay: $workDay,
            sourceAccount: $bca,
            destinationAccount: $shopeePay,
            amount: 50000,
            adminFee: 0
        );

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertSame('transfer', $tx->type);

        $bca->refresh();
        $shopeePay->refresh();

        // BCA berkurang 50k -> 150k
        $this->assertSame(150000, $bca->current_balance);
        // ShopeePay bertambah 50k -> 60k
        $this->assertSame(60000, $shopeePay->current_balance);
    }

    public function test_transfer_with_admin_fee_deducts_source_and_records_admin_expense(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $bca = Account::where('name', 'BCA')->firstOrFail();
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $adminExpense = Account::where('name', 'Beban Biaya Admin Transfer')->firstOrFail();

        $bca->update(['current_balance' => 200000]);
        $shopeePay->update(['current_balance' => 0]);
        $adminExpense->update(['current_balance' => 0]);

        $tx = $this->transferService->transfer(
            workDay: $workDay,
            sourceAccount: $bca,
            destinationAccount: $shopeePay,
            amount: 100000,
            adminFee: 1000
        );

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertCount(3, $tx->ledgerEntries);

        $bca->refresh();
        $shopeePay->refresh();
        $adminExpense->refresh();

        // BCA dipotong 100k + 1k = 101k -> sisa 99k
        $this->assertSame(99000, $bca->current_balance);
        // ShopeePay bertambah nominal transfer murni -> 100k
        $this->assertSame(100000, $shopeePay->current_balance);
        // Beban Admin bertambah 1k
        $this->assertSame(1000, $adminExpense->current_balance);
    }

    public function test_cannot_transfer_to_the_same_account(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $bca = Account::where('name', 'BCA')->firstOrFail();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Akun sumber dan akun tujuan tidak boleh sama.');

        $this->transferService->transfer(
            workDay: $workDay,
            sourceAccount: $bca,
            destinationAccount: $bca,
            amount: 50000
        );
    }

    public function test_cannot_transfer_zero_or_negative_amount(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $bca = Account::where('name', 'BCA')->firstOrFail();
        $cash = Account::where('name', 'Cash')->firstOrFail();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nominal transfer harus lebih besar dari Rp0.');

        $this->transferService->transfer(
            workDay: $workDay,
            sourceAccount: $bca,
            destinationAccount: $cash,
            amount: 0
        );
    }
}
