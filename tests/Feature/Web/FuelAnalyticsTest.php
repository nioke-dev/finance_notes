<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\QuickFuelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected QuickFuelService $fuelService;
    protected Account $cashAccount;
    protected WorkDay $workDay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fuelService = app(QuickFuelService::class);

        $this->cashAccount = Account::create([
            'name' => 'Cash',
            'type' => AccountType::CASH,
            'current_balance' => 200000,
            'initial_balance' => 200000,
        ]);

        Account::create([
            'name' => 'Beban Bensin Kendaraan',
            'type' => AccountType::EXPENSE,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_FINAL,
        ]);
    }

    public function test_fuel_analytics_calculates_spbu_and_madura_breakdown_correctly(): void
    {
        // Pembelian bensin SPBU Rp20.000 (~2.0 Liter)
        $this->fuelService->recordFuel(
            workDay: $this->workDay,
            sourceAccount: $this->cashAccount,
            vendorType: 'spbu',
            amount: 20000
        );

        // Pembelian bensin Madura Rp24.000 (~2 Botol)
        $this->fuelService->recordFuel(
            workDay: $this->workDay,
            sourceAccount: $this->cashAccount,
            vendorType: 'madura',
            amount: 24000
        );

        $response = $this->get('/analytics/fuel');

        $response->assertStatus(200);
        $response->assertSee('Analitik Pengeluaran Bensin');
        $response->assertSee('Rp 20.000');
        $response->assertSee('Rp 24.000');
        // Total Spent = 20.000 + 24.000 = 44.000
        $response->assertSee('Rp 44.000');
        $response->assertSee('SPBU Resmi');
        $response->assertSee('Warung Madura');
    }
}
