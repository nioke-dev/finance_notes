<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmBonusDisbursementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_confirming_bonus_credits_shopeepay_balance_accurately(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $shopeepayAccount = Account::where('name', 'ShopeePay')->first();
        $shopeepayAccount->update(['current_balance' => 500000]);

        $payload = [
            'work_day_id' => $workDay->id,
            'is_disbursed' => true,
            'bonus_amount' => 14500,
        ];

        $response = $this->postJson('/api/v1/driver/confirm-bonus-disbursement', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Bonus Hub berhasil dicairkan ke saldo ShopeePay.',
                'data' => [
                    'is_disbursed' => true,
                    'credited_amount' => 14500,
                    'shopeepay_current_balance' => 514500,
                ],
            ]);

        $shopeepayAccount->refresh();
        $this->assertEquals(514500, $shopeepayAccount->current_balance);

        $bonusIncomeAccount = Account::where('name', 'Bonus Hub ShopeeFood')->first();
        $this->assertEquals(14500, $bonusIncomeAccount->current_balance);
    }

    public function test_unconfirmed_bonus_leaves_shopeepay_balance_untouched(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $shopeepayAccount = Account::where('name', 'ShopeePay')->first();
        $shopeepayAccount->update(['current_balance' => 500000]);

        $payload = [
            'work_day_id' => $workDay->id,
            'is_disbursed' => false,
            'bonus_amount' => 14500,
        ];

        $response = $this->postJson('/api/v1/driver/confirm-bonus-disbursement', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Status pencairan bonus ditandai belum masuk. Saldo ShopeePay belum disesuaikan.',
                'data' => [
                    'is_disbursed' => false,
                    'credited_amount' => 0,
                ],
            ]);

        $shopeepayAccount->refresh();
        $this->assertEquals(500000, $shopeepayAccount->current_balance);

        $bonusIncomeAccount = Account::where('name', 'Bonus Hub ShopeeFood')->first();
        $this->assertEquals(0, $bonusIncomeAccount->current_balance);
    }
}
