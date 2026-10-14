<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_can_record_cod_with_surplus_via_api(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 50000]);

        $payload = [
            'work_day_id' => $workDay->id,
            'order_number' => 'SPF-100829',
            'bill_amount' => 15125,
            'received_amount' => 16000,
        ];

        $response = $this->postJson('/api/v1/quick/cod', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Transaksi COD berhasil dicatat.',
                'data' => [
                    'bill_amount' => 15125,
                    'received_amount' => 16000,
                    'surplus_tip' => 875,
                    'order_number' => 'SPF-100829',
                ],
            ]);

        $cashAccount->refresh();
        $this->assertEquals(66000, $cashAccount->current_balance);

        $tipAccount = Account::where('name', 'Tip Cash / Surplus COD')->first();
        $this->assertEquals(875, $tipAccount->current_balance);
    }

    public function test_cod_fails_if_received_less_than_bill_via_api(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'bill_amount' => 20000,
            'received_amount' => 15000,
        ];

        $response = $this->postJson('/api/v1/quick/cod', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['received_amount']);
    }
}
