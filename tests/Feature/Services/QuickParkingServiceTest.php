<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\WorkDay;
use App\Services\QuickParkingService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickParkingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuickParkingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultAccountsSeeder::class);
        $this->service = app(QuickParkingService::class);
    }

    public function test_record_parking_deducts_cash_and_adds_parking_expense(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 50000]);

        $parkingExpenseAccount = Account::where('name', 'Beban Parkir')->first();
        $parkingExpenseAccount->update(['current_balance' => 0]);

        $transaction = $this->service->recordParking(
            workDay: $workDay,
            amount: 1000,
            restaurantName: 'Mie Gacoan Malang'
        );

        $this->assertEquals(1000, $transaction->amount);
        $this->assertEquals('Parkir Motor: Mie Gacoan Malang', $transaction->description);

        $cashAccount->refresh();
        $this->assertEquals(49000, $cashAccount->current_balance);

        $parkingExpenseAccount->refresh();
        $this->assertEquals(1000, $parkingExpenseAccount->current_balance);
    }

    public function test_record_parking_stores_gps_coordinates_in_metadata(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 20000]);

        $transaction = $this->service->recordParking(
            workDay: $workDay,
            amount: 1000,
            restaurantName: 'Kober Mie Setan',
            latitude: -7.9666,
            longitude: 112.6326
        );

        $this->assertNotNull($transaction->metadata);
        $this->assertEquals(-7.9666, $transaction->metadata['latitude']);
        $this->assertEquals(112.6326, $transaction->metadata['longitude']);
        $this->assertEquals('Kober Mie Setan', $transaction->metadata['restaurant_name']);
    }

    public function test_record_parking_succeeds_without_restaurant_name(): void
    {
        $workDay = WorkDay::create([
            'work_date' => '2026-10-14',
            'status' => 'active',
        ]);

        $cashAccount = Account::where('name', 'Cash')->first();
        $cashAccount->update(['current_balance' => 20000]);

        $transaction = $this->service->recordParking(
            workDay: $workDay,
            amount: 1000
        );

        $this->assertEquals('Parkir Motor Lapangan (Quick 1-Tap)', $transaction->description);
        $this->assertNull($transaction->metadata['restaurant_name']);
    }
}
