<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendDebtPaymentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_can_settle_friend_debt_with_forgiveness_via_api(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 500000]);

        $debt = Debt::create([
            'name' => 'Talangan Rofik',
            'platform' => 'person',
            'borrower_name' => 'Rofik',
            'principal_amount' => 249000,
            'fee_amount' => 0,
            'total_obligation' => 249000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => '2026-10-14',
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'debt_id' => $debt->id,
            'account_id' => $bca->id,
            'received_amount' => 248000,
            'forgive_remainder' => true,
        ];

        $response = $this->postJson('/api/v1/debts/friend-payment', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pembayaran utang teman berhasil dicatat.',
                'data' => [
                    'borrower_name' => 'Rofik',
                    'received_amount' => 248000,
                    'remainder' => 1000,
                    'is_paid_off' => true,
                    'charity_recorded' => 1000,
                ],
            ]);

        $this->assertEquals(748000, $bca->fresh()->current_balance);
        $this->assertTrue($debt->fresh()->isPaidOff());
    }

    public function test_friend_debt_payment_fails_for_non_existent_debt(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bca = Account::where('name', 'BCA')->first();

        $payload = [
            'work_day_id' => $workDay->id,
            'debt_id' => 999999,
            'account_id' => $bca->id,
            'received_amount' => 100000,
            'forgive_remainder' => false,
        ];

        $response = $this->postJson('/api/v1/debts/friend-payment', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['debt_id']);
    }
}
