<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnfinalizedDatesBannerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Account::firstOrCreate(
            ['code' => '111'],
            ['name' => 'Cash', 'type' => \App\Enums\AccountType::CASH, 'balance' => 0]
        );
        Account::firstOrCreate(
            ['code' => '112'],
            ['name' => 'BCA Digital', 'type' => \App\Enums\AccountType::BANK, 'balance' => 0]
        );
    }

    public function test_returns_pending_days_with_banner_messages(): void
    {
        // Hari kemarin berstatus needs_reconciliation
        $yesterday = now()->subDay()->toDateString();
        $workDay = WorkDay::create([
            'work_date' => $yesterday,
            'start_time' => '08:00:00',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $response = $this->getJson('/api/v1/reconciliation/unfinalized-dates');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'work_day_id',
                        'work_date',
                        'human_date',
                        'status',
                        'total_discrepancy',
                        'message',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($workDay->id, $data[0]['work_day_id']);
        $this->assertStringContainsString('belum difinalisasi', $data[0]['message']);
    }

    public function test_returns_empty_when_all_past_days_are_finalized(): void
    {
        // Hari kemarin berstatus final
        $yesterday = now()->subDay()->toDateString();
        WorkDay::create([
            'work_date' => $yesterday,
            'start_time' => '08:00:00',
            'status' => WorkDay::STATUS_FINAL,
        ]);

        $response = $this->getJson('/api/v1/reconciliation/unfinalized-dates');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [],
            ]);
    }
}
