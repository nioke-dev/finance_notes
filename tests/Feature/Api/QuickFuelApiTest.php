<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickFuelApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_quick_fuel_api_succeeds_and_returns_volume_data(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bcaAccount = Account::where('name', 'BCA')->first();
        $bcaAccount->update(['current_balance' => 150000]);

        $payload = [
            'work_day_id' => $workDay->id,
            'account_id' => $bcaAccount->id,
            'vendor_type' => 'spbu',
            'amount' => 30000,
        ];

        $response = $this->postJson('/api/v1/quick/fuel', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengeluaran bensin berhasil dicatat.',
                'data' => [
                    'amount' => 30000,
                    'vendor_type' => 'spbu',
                    'estimated_volume' => 3.0,
                    'account_name' => 'BCA',
                ],
            ]);

        $bcaAccount->refresh();
        $this->assertEquals(120000, $bcaAccount->current_balance);

        $fuelExpenseAccount = Account::where('name', 'Beban Bensin Kendaraan')->first();
        $this->assertEquals(30000, $fuelExpenseAccount->current_balance);
    }

    public function test_quick_fuel_api_rejects_invalid_vendor_type(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bcaAccount = Account::where('name', 'BCA')->first();

        $payload = [
            'work_day_id' => $workDay->id,
            'account_id' => $bcaAccount->id,
            'vendor_type' => 'pertamax',
            'amount' => 30000,
        ];

        $response = $this->postJson('/api/v1/quick/fuel', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vendor_type']);
    }

    public function test_quick_fuel_api_rejects_amount_under_one_thousand(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bcaAccount = Account::where('name', 'BCA')->first();

        $payload = [
            'work_day_id' => $workDay->id,
            'account_id' => $bcaAccount->id,
            'vendor_type' => 'spbu',
            'amount' => 500,
        ];

        $response = $this->postJson('/api/v1/quick/fuel', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }
}
