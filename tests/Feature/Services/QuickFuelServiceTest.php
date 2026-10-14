<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\WorkDay;
use App\Services\QuickFuelService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class QuickFuelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuickFuelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = app(QuickFuelService::class);
    }

    public function test_record_fuel_spbu_calculates_correct_liters_and_deducts_source(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $bcaAccount = Account::where('name', 'BCA')->first();
        $bcaAccount->update(['current_balance' => 200000]);

        $fuelExpenseAccount = Account::where('name', 'Beban Bensin Kendaraan')->first();
        $fuelExpenseAccount->update(['current_balance' => 0]);

        $transaction = $this->service->recordFuel(
            workDay: $workDay,
            sourceAccount: $bcaAccount,
            vendorType: 'spbu',
            amount: 30000
        );

        $this->assertEquals(30000, $transaction->amount);
        $this->assertEquals('expense', $transaction->type);
        $this->assertEquals('quick_fuel', $transaction->metadata['type']);
        $this->assertEquals('spbu', $transaction->metadata['vendor_type']);
        $this->assertEquals(10000, $transaction->metadata['unit_price']);
        $this->assertEquals(3.0, $transaction->metadata['estimated_volume']);

        $bcaAccount->refresh();
        $this->assertEquals(170000, $bcaAccount->current_balance);

        $fuelExpenseAccount->refresh();
        $this->assertEquals(30000, $fuelExpenseAccount->current_balance);
    }

    public function test_record_fuel_madura_calculates_correct_volume_and_deducts_cash(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 50000]);

        $fuelExpenseAccount = Account::where('name', 'Beban Bensin Kendaraan')->first();
        $fuelExpenseAccount->update(['current_balance' => 0]);

        $transaction = $this->service->recordFuel(
            workDay: $workDay,
            sourceAccount: $cashAccount,
            vendorType: 'madura',
            amount: 24000
        );

        $this->assertEquals(24000, $transaction->amount);
        $this->assertEquals('madura', $transaction->metadata['vendor_type']);
        $this->assertEquals(12000, $transaction->metadata['unit_price']);
        $this->assertEquals(2.0, $transaction->metadata['estimated_volume']);

        $cashAccount->refresh();
        $this->assertEquals(26000, $cashAccount->current_balance);

        $fuelExpenseAccount->refresh();
        $this->assertEquals(24000, $fuelExpenseAccount->current_balance);
    }

    public function test_invalid_vendor_type_throws_exception(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tipe vendor bensin harus 'spbu' atau 'madura'.");

        $this->service->recordFuel(
            workDay: $workDay,
            sourceAccount: $cashAccount,
            vendorType: 'shell',
            amount: 30000
        );
    }
}
