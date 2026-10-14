<?php

namespace Tests\Feature\Api;

use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverSnapshotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_store_valid_driver_snapshot_via_api(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
            'notes' => 'Hari kerja Malang',
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'completed_orders' => 22,
            'hub_income' => 176000,
            'hub_bonus' => 8000,
            'customer_tips' => 15000,
            'shopeepay_balance' => 520000,
            'gopay_balance' => 185000,
            'gopay_merchant_balance' => 8500,
        ];

        $response = $this->postJson('/api/v1/driver/snapshot', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Snapshot progres driver berhasil disimpan.',
                'data' => [
                    'total_driver_income' => 176000 + 8000 + 15000, // 199,000
                    'total_digital_holding' => 520000 + 185000 + 8500, // 713,500
                    'merchant_settlement_status' => 'holding',
                ],
            ]);

        $this->assertDatabaseHas('driver_snapshots', [
            'work_day_id' => $workDay->id,
            'completed_orders' => 22,
            'hub_income' => 176000,
            'hub_bonus' => 8000,
            'customer_tips' => 15000,
            'shopeepay_balance' => 520000,
            'gopay_balance' => 185000,
            'gopay_merchant_balance' => 8500,
        ]);
    }

    public function test_snapshot_fails_validation_if_any_balance_is_negative(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'completed_orders' => 10,
            'hub_income' => 80000,
            'hub_bonus' => 0,
            'customer_tips' => 5000,
            'shopeepay_balance' => -1000, // Invalid negative
            'gopay_balance' => 50000,
            'gopay_merchant_balance' => 0,
        ];

        $response = $this->postJson('/api/v1/driver/snapshot', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shopeepay_balance']);
    }
}
