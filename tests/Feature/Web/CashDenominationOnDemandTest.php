<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\ActiveCodOrder;
use App\Models\CashDenomination;
use App\Models\DriverSnapshot;
use App\Models\User;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashDenominationOnDemandTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WorkDay $workDay;
    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->seed(\Database\Seeders\DefaultAccountsSeeder::class);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'day_type' => WorkDay::TYPE_ON_BID,
            'status' => 'active',
        ]);

        $this->cashAccount = Account::where('name', 'Cash')->firstOrFail();
    }

    public function test_can_update_cash_denominations_on_demand_and_syncs_cash_account_and_checkpoint(): void
    {
        // Checkpoint awal
        $snapshot = DriverSnapshot::create([
            'work_day_id' => $this->workDay->id,
            'recorded_at' => now()->subHour(),
            'completed_orders' => 5,
            'delivery_fee' => 45000,
            'hub_income' => 0,
            'hub_bonus' => 0,
            'customer_tips' => 0,
            'shopeepay_balance' => 50000,
            'gopay_balance' => 10000,
            'gopay_merchant_balance' => 0,
            'cash_balance' => 0,
        ]);

        $response = $this->post(route('operational.cash-denominations'), [
            'work_day_id' => $this->workDay->id,
            'bill_100000' => 1,
            'bill_50000' => 2,
            'bill_20000' => 1,
            'bill_5000' => 3,
            'coin_1000' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Total: (1x100k) + (2x50k) + (1x20k) + (3x5k) + (2x1k) = 100k + 100k + 20k + 15k + 2k = 237k
        $expectedTotal = 237000;

        $denom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        $this->assertNotNull($denom);
        $this->assertEquals($expectedTotal, $denom->total_physical_amount);
        $this->assertEquals(1, $denom->bill_100000);
        $this->assertEquals(2, $denom->bill_50000);
        $this->assertEquals(1, $denom->bill_20000);
        $this->assertEquals(3, $denom->bill_5000);
        $this->assertEquals(2, $denom->coin_1000);

        // Saldo akun kas tersinkron
        $this->assertEquals($expectedTotal, $this->cashAccount->fresh()->current_balance);

        // Checkpoint DriverSnapshot tersinkron
        $this->assertEquals($expectedTotal, $snapshot->fresh()->cash_balance);

        // Audit log tercatat dengan benar
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => CashDenomination::class,
            'auditable_id' => $denom->id,
            'action' => 'cash_denom_update',
        ]);
    }

    public function test_can_record_fuel_with_optional_cash_breakdown_and_updates_denomination(): void
    {
        // Denominasi awal: 2 lembar 50k (Total Rp100.000)
        CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_50000' => 2,
            'bill_20000' => 0,
            'total_physical_amount' => 100000,
        ]);
        $this->cashAccount->update(['current_balance' => 100000]);

        $response = $this->post(route('operational.fuel'), [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $this->cashAccount->id,
            'vendor_type' => 'spbu',
            'amount' => 30000,
            'paid_breakdown' => [
                'bill_50000' => 1,
            ],
            'change_breakdown' => [
                'bill_20000' => 1,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Denominasi baru: bill_50000 berkurang 1 (sisa 1), bill_20000 bertambah 1 (jadi 1)
        // Total = 50k + 20k = 70k
        $denom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        $this->assertEquals(1, $denom->bill_50000);
        $this->assertEquals(1, $denom->bill_20000);
        $this->assertEquals(70000, $denom->total_physical_amount);
    }

    public function test_can_record_parking_with_optional_cash_breakdown(): void
    {
        CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_5000' => 2,
            'bill_2000' => 1,
            'coin_1000' => 0,
            'total_physical_amount' => 12000,
        ]);

        $response = $this->post(route('operational.parking'), [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $this->cashAccount->id,
            'restaurant_name' => 'Gacoan',
            'amount' => 2000,
            'paid_breakdown' => [
                'bill_5000' => 1,
            ],
            'change_breakdown' => [
                'bill_2000' => 1,
                'coin_1000' => 1,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $denom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        // bill_5000 sisa 1, bill_2000 jadi 2, coin_1000 jadi 1 -> total 5k + 4k + 1k = 10k
        $this->assertEquals(1, $denom->bill_5000);
        $this->assertEquals(2, $denom->bill_2000);
        $this->assertEquals(1, $denom->coin_1000);
        $this->assertEquals(10000, $denom->total_physical_amount);
    }

    public function test_can_complete_cod_order_with_optional_cash_breakdown(): void
    {

        $order = ActiveCodOrder::create([
            'work_day_id' => $this->workDay->id,
            'order_code' => 'COD-99',
            'bill_amount' => 45000,
            'rounded_bill_amount' => 45000,
            'status' => 'on_delivery',
            'accepted_at' => now(),
        ]);

        CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_50000' => 0,
            'bill_5000' => 2,
            'total_physical_amount' => 10000,
        ]);

        $response = $this->post(route('operational.cod-orders.complete', $order->id), [
            'received_amount' => 45000,
            'payment_method' => 'cash',
            'customer_gave_amount' => 50000,
            'change_returned' => 5000,
            'customer_cash_breakdown' => [
                'bill_50000' => 1,
            ],
            'change_cash_breakdown' => [
                'bill_5000' => 1,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $denom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        // bill_50000 bertambah 1, bill_5000 berkurang 1 (sisa 1) -> Total 50k + 5k = 55k
        $this->assertEquals(1, $denom->bill_50000);
        $this->assertEquals(1, $denom->bill_5000);
        $this->assertEquals(55000, $denom->total_physical_amount);
    }

    public function test_optional_cash_breakdown_can_be_omitted_safely(): void
    {
        $response = $this->post(route('operational.fuel'), [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $this->cashAccount->id,
            'vendor_type' => 'spbu',
            'amount' => 15000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_dashboard_renders_quick_cash_modal_and_trigger_buttons(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('modal-quick-cash-denominations');
        $response->assertSee('Update Kas Fisik di Jalan');
        $response->assertSee('Total Uang Fisik Saku:');
        $response->assertSee('Rincian Pecahan Cash & Kembalian', false);
    }
}
