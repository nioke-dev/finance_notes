<?php

namespace Tests\Feature\Api;

use App\Models\WorkDay;
use Database\Seeders\ShopeeBonusSchemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluatePerformanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShopeeBonusSchemeSeeder::class);
    }

    public function test_driver_with_9_hours_online_qualifies_for_calculated_bonus(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'online_hours' => 9.15,
            'peak_hour_rate' => 85.0,
            'completion_rate' => 95.0,
            'eligible_orders_count' => 26,
        ];

        $response = $this->postJson('/api/v1/driver/evaluate-performance', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'work_day_id' => $workDay->id,
                    'is_eligible' => true,
                    'calculated_bonus' => 14500,
                ],
            ]);

        $workDay->refresh();
        $this->assertTrue($workDay->is_performance_eligible);
        $this->assertSame(9.15, (float) $workDay->online_hours);
        $this->assertSame(26, $workDay->total_orders);
    }

    public function test_driver_with_under_9_hours_online_gets_zero_bonus_even_with_high_orders(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'online_hours' => 8.5,
            'peak_hour_rate' => 90.0,
            'completion_rate' => 98.0,
            'eligible_orders_count' => 30,
        ];

        $response = $this->postJson('/api/v1/driver/evaluate-performance', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'work_day_id' => $workDay->id,
                    'is_eligible' => false,
                    'calculated_bonus' => 0,
                ],
            ]);

        $workDay->refresh();
        $this->assertFalse($workDay->is_performance_eligible);
    }

    public function test_driver_with_low_peak_or_completion_rate_gets_zero_bonus(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'online_hours' => 10.0,
            'peak_hour_rate' => 65.0, // di bawah minimum 70.0%
            'completion_rate' => 95.0,
            'eligible_orders_count' => 25,
        ];

        $response = $this->postJson('/api/v1/driver/evaluate-performance', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'is_eligible' => false,
                    'calculated_bonus' => 0,
                ],
            ]);
    }
}
