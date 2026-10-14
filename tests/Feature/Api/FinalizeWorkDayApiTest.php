<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalizeWorkDayApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
    }

    public function test_successful_finalization_returns_200_ok(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::where('name', 'Cash')->first();
        $cash->update(['current_balance' => 100000]);

        CashDenomination::create([
            'work_day_id' => $workDay->id,
            'bill_100000' => 1,
        ]);

        $bca = Account::where('name', 'BCA')->first();
        $bca->update(['current_balance' => 500000]);

        $payload = [
            'work_day_id' => $workDay->id,
            'digital_balances' => [
                ['account_id' => $bca->id, 'actual_balance' => 500000],
            ],
            'closing_notes' => 'Semua mutasi sudah cocok dan fisik dihitung pas.',
        ];

        $response = $this->postJson('/api/v1/reconciliation/finalize-day', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Hari kerja berhasil difinalisasi. Selisih Rp0.',
                'data' => [
                    'work_day_id' => $workDay->id,
                    'status' => WorkDay::STATUS_FINAL,
                ],
            ]);

        $this->assertEquals(WorkDay::STATUS_FINAL, $workDay->fresh()->status);
        $this->assertNotNull($workDay->fresh()->closed_at);
    }

    public function test_unbalanced_finalization_returns_422_with_discrepant_accounts(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::where('name', 'Cash')->first();
        $cash->update(['current_balance' => 350000]);

        // Fisik aktual Rp335.000 (Shortage Rp15.000)
        CashDenomination::create([
            'work_day_id' => $workDay->id,
            'bill_100000' => 3,
            'bill_20000' => 1,
            'bill_10000' => 1,
            'bill_5000' => 1,
        ]);

        $payload = [
            'work_day_id' => $workDay->id,
            'digital_balances' => [],
        ];

        $response = $this->postJson('/api/v1/reconciliation/finalize-day', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'discrepant_accounts' => [
                    [
                        'account_name' => 'Cash',
                        'discrepancy' => -15000,
                        'status' => 'shortage',
                    ],
                ],
            ]);

        $this->assertEquals(WorkDay::STATUS_NEEDS_RECONCILIATION, $workDay->fresh()->status);
        $this->assertNull($workDay->fresh()->closed_at);
    }
}
