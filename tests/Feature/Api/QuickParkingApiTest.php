<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickParkingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_quick_parking_api_succeeds_with_minimal_payload(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 50000]);

        $payload = [
            'work_day_id' => $workDay->id,
        ];

        $response = $this->postJson('/api/v1/quick/parking', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Biaya parkir berhasil dicatat.',
                'data' => [
                    'amount' => 1000,
                    'restaurant_name' => null,
                ],
            ]);

        $cashAccount->refresh();
        $this->assertEquals(49000, $cashAccount->current_balance);

        $parkingExpenseAccount = Account::where('name', 'Beban Parkir')->first();
        $this->assertEquals(1000, $parkingExpenseAccount->current_balance);
    }

    public function test_quick_parking_api_saves_full_details_when_provided(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 50000]);

        $payload = [
            'work_day_id' => $workDay->id,
            'amount' => 2000,
            'restaurant_name' => 'Mie Gacoan Sutoyo',
            'latitude' => -7.966620,
            'longitude' => 112.632632,
        ];

        $response = $this->postJson('/api/v1/quick/parking', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Biaya parkir berhasil dicatat.',
                'data' => [
                    'amount' => 2000,
                    'restaurant_name' => 'Mie Gacoan Sutoyo',
                ],
            ]);

        $cashAccount->refresh();
        $this->assertEquals(48000, $cashAccount->current_balance);

        $parkingExpenseAccount = Account::where('name', 'Beban Parkir')->first();
        $this->assertEquals(2000, $parkingExpenseAccount->current_balance);
    }

    public function test_quick_parking_fails_for_non_existent_work_day(): void
    {
        $payload = [
            'work_day_id' => 999999,
            'amount' => 1000,
        ];

        $response = $this->postJson('/api/v1/quick/parking', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['work_day_id']);
    }
}
