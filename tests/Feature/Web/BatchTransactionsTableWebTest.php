<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchTransactionsTableWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $cashAccount;
    protected Account $shopeePayAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->cashAccount = Account::create([
            'name' => 'Cash',
            'type' => AccountType::CASH,
            'current_balance' => 100000,
            'initial_balance' => 100000,
            'is_active' => true,
        ]);

        $this->shopeePayAccount = Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'current_balance' => 200000,
            'initial_balance' => 200000,
            'is_active' => true,
        ]);
    }

    public function test_accessing_dashboard_with_date_filter_initializes_work_day_and_renders_table(): void
    {
        $targetDate = '2026-10-25';
        $this->assertDatabaseMissing('work_days', ['work_date' => $targetDate]);

        $response = $this->actingAs($this->user)->get('/dashboard?date=' . $targetDate);
        $response->assertStatus(200);

        // Shift tanggal tersebut otomatis terbuat dan aktif
        $this->assertDatabaseHas('work_days', [
            'work_date' => $targetDate,
            'day_type' => WorkDay::TYPE_ON_BID,
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        // Memastikan tabel input batch multi-tanggal dirender
        $response->assertSee('Tabel Input Transaksi Multi-Tanggal');
        $response->assertSee('form-batch-transactions');
    }

    public function test_driver_can_submit_batch_transactions_across_different_dates(): void
    {
        $date20 = '2026-10-20';
        $date21 = '2026-10-21';
        $date22 = '2026-10-22';

        $payload = [
            'entries' => [
                [
                    'transaction_date' => $date20,
                    'transaction_time' => '07:30',
                    'category' => 'expense',
                    'description' => 'Sarapan pagi tgl 20',
                    'account_id' => $this->cashAccount->id,
                    'amount' => 15000,
                ],
                [
                    'transaction_date' => $date20,
                    'transaction_time' => '13:00',
                    'category' => 'fuel',
                    'description' => 'Bensin SPBU tgl 20',
                    'account_id' => $this->cashAccount->id,
                    'amount' => 25000,
                ],
                [
                    'transaction_date' => $date21,
                    'transaction_time' => '12:15',
                    'category' => 'parking',
                    'description' => 'Parkir Mie Gacoan tgl 21',
                    'account_id' => $this->cashAccount->id,
                    'amount' => 2000,
                ],
                [
                    'transaction_date' => $date21,
                    'transaction_time' => '18:30',
                    'category' => 'personal_income',
                    'description' => 'Honor freelance tgl 21',
                    'account_id' => $this->shopeePayAccount->id,
                    'amount' => 50000,
                ],
                [
                    'transaction_date' => $date22,
                    'transaction_time' => '09:00',
                    'category' => 'tip',
                    'description' => 'Tip dari customer COD tgl 22',
                    'account_id' => $this->cashAccount->id,
                    'amount' => 5000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('operational.batch-transactions'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan masing-masing WorkDay terbuat untuk tanggal 20, 21, dan 22
        $this->assertDatabaseHas('work_days', ['work_date' => $date20]);
        $this->assertDatabaseHas('work_days', ['work_date' => $date21]);
        $this->assertDatabaseHas('work_days', ['work_date' => $date22]);

        $workDay20 = WorkDay::where('work_date', $date20)->firstOrFail();
        $workDay21 = WorkDay::where('work_date', $date21)->firstOrFail();
        $workDay22 = WorkDay::where('work_date', $date22)->firstOrFail();

        // Cek transaksi di masing-masing tanggal
        $this->assertEquals(2, Transaction::where('work_day_id', $workDay20->id)->count());
        $this->assertEquals(2, Transaction::where('work_day_id', $workDay21->id)->count());
        $this->assertEquals(1, Transaction::where('work_day_id', $workDay22->id)->count());

        // Verifikasi saldo akun terupdate sesuai ledger
        // Cash awal 100.000 - 15.000 - 25.000 - 2.000 + 5.000 = 63.000
        $this->assertEquals(63000, $this->cashAccount->fresh()->current_balance);
        // ShopeePay awal 200.000 + 50.000 = 250.000
        $this->assertEquals(250000, $this->shopeePayAccount->fresh()->current_balance);
    }
}
