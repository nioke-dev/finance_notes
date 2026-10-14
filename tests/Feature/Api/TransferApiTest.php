<?php

namespace Tests\Feature\Api;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_successful_transfer_returns_201_created_and_updates_balances(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $bca = Account::where('name', 'BCA')->firstOrFail();
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $adminExpense = Account::where('name', 'Beban Biaya Admin Transfer')->firstOrFail();

        $bca->update(['current_balance' => 250000]);
        $shopeePay->update(['current_balance' => 20000]);

        $payload = [
            'work_day_id' => $workDay->id,
            'from_account_id' => $bca->id,
            'to_account_id' => $shopeePay->id,
            'amount' => 100000,
            'admin_fee' => 1000,
            'note' => 'Top Up ShopeePay dari BCA',
        ];

        $response = $this->postJson('/api/v1/transfers', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Transfer berhasil dicatat.',
                'data' => [
                    'from_account' => 'BCA',
                    'to_account' => 'ShopeePay',
                    'amount' => 100000,
                    'admin_fee' => 1000,
                ],
            ]);

        $bca->refresh();
        $shopeePay->refresh();
        $adminExpense->refresh();

        // BCA dipotong 100k + 1k = 101k -> sisa 149k
        $this->assertSame(149000, $bca->current_balance);
        // ShopeePay bertambah 100k -> 120k
        $this->assertSame(120000, $shopeePay->current_balance);
        // Beban admin bertambah 1k
        $this->assertSame(1000, $adminExpense->current_balance);
    }

    public function test_transfer_to_same_account_returns_422_validation_error(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $bca = Account::where('name', 'BCA')->firstOrFail();

        $payload = [
            'work_day_id' => $workDay->id,
            'from_account_id' => $bca->id,
            'to_account_id' => $bca->id,
            'amount' => 50000,
        ];

        $response = $this->postJson('/api/v1/transfers', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_transfer_with_negative_admin_fee_returns_422(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $bca = Account::where('name', 'BCA')->firstOrFail();
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();

        $payload = [
            'work_day_id' => $workDay->id,
            'from_account_id' => $bca->id,
            'to_account_id' => $shopeePay->id,
            'amount' => 50000,
            'admin_fee' => -500,
        ];

        $response = $this->postJson('/api/v1/transfers', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['admin_fee']);
    }

    public function test_transfer_with_non_existent_work_day_or_account_returns_422(): void
    {
        $payload = [
            'work_day_id' => 9999,
            'from_account_id' => 8888,
            'to_account_id' => 7777,
            'amount' => 50000,
        ];

        $response = $this->postJson('/api/v1/transfers', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['work_day_id', 'from_account_id', 'to_account_id']);
    }
}
