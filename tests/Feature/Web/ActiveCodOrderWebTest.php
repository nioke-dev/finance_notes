<?php

namespace Tests\Feature\Web;

use App\Models\Account;
use App\Models\ActiveCodOrder;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveCodOrderWebTest extends TestCase
{
    use RefreshDatabase;

    protected WorkDay $workDay;
    protected Account $shopeePay;
    protected Account $cash;
    protected Account $goPay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DefaultAccountsSeeder::class);

        $this->shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $this->shopeePay->update(['current_balance' => 100000, 'initial_balance' => 100000]);

        $this->cash = Account::where('name', 'Cash')->firstOrFail();
        $this->cash->update(['current_balance' => 150000, 'initial_balance' => 150000]);

        $this->goPay = Account::where('name', 'GoPay')->firstOrFail();
        $this->goPay->update(['current_balance' => 50000, 'initial_balance' => 50000]);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);
    }

    public function test_user_can_accept_new_cod_order_via_web(): void
    {
        $response = $this->post('/operational/cod-orders/accept', [
            'work_day_id' => $this->workDay->id,
            'order_code' => '633',
            'bill_amount' => 51700,
            'merchant_name' => 'Geprek Kak Rose Dinoyo',
            'latitude' => -7.9456,
            'longitude' => 112.6145,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('active_cod_orders', [
            'work_day_id' => $this->workDay->id,
            'order_code' => '633',
            'bill_amount' => 51700,
            'rounded_bill_amount' => 52000,
            'status' => 'on_delivery',
        ]);

        // Saldo ShopeePay berkurang
        $this->assertEquals(48300, $this->shopeePay->fresh()->current_balance);
    }

    public function test_dashboard_renders_active_cod_orders_banner_and_calculator_trigger(): void
    {
        ActiveCodOrder::create([
            'work_day_id' => $this->workDay->id,
            'order_code' => '633',
            'merchant_name' => 'Geprek Kak Rose',
            'bill_amount' => 51700,
            'rounded_bill_amount' => 52000,
            'status' => 'on_delivery',
            'accepted_at' => now(),
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Pesanan COD Sedang Diantar');
        $response->assertSee('#633');
        $response->assertSee('Geprek Kak Rose');
        $response->assertSee('Buka Kalkulator & Selesaikan (#633)', false);
        $response->assertSee('modal-complete-cod');
    }

    public function test_user_can_complete_cod_order_cash_with_calculator_via_web(): void
    {
        $order = ActiveCodOrder::create([
            'work_day_id' => $this->workDay->id,
            'order_code' => '633',
            'merchant_name' => 'Geprek Kak Rose',
            'bill_amount' => 51700,
            'rounded_bill_amount' => 52000,
            'status' => 'on_delivery',
            'accepted_at' => now(),
        ]);

        // Customer ngasih 100k, minta kembalian 20k -> uang masuk 80.000
        $response = $this->post("/operational/cod-orders/{$order->id}/complete", [
            'received_amount' => 80000,
            'payment_method' => 'cash',
            'customer_gave_amount' => 100000,
            'change_returned' => 20000,
            'parking_reimbursed' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('delivered', $order->fresh()->status);
        $this->assertEquals(80000, $order->fresh()->received_amount);
        $this->assertEquals(28300, $order->fresh()->tip_amount);

        // Kas bertambah 80.000 (150.000 + 80.000 = 230.000)
        $this->assertEquals(230000, $this->cash->fresh()->current_balance);
    }

    public function test_user_can_complete_cod_order_qris_via_web(): void
    {
        $order = ActiveCodOrder::create([
            'work_day_id' => $this->workDay->id,
            'order_code' => '385',
            'merchant_name' => 'Mie Gacoan Sulfat',
            'bill_amount' => 34200,
            'rounded_bill_amount' => 35000,
            'status' => 'on_delivery',
            'accepted_at' => now(),
        ]);

        $response = $this->post("/operational/cod-orders/{$order->id}/complete", [
            'received_amount' => 40000,
            'payment_method' => 'qris',
            'settlement_account_id' => $this->goPay->id,
            'parking_reimbursed' => 2000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('delivered', $order->fresh()->status);
        $this->assertEquals(90000, $this->goPay->fresh()->current_balance);
    }

    public function test_user_can_cancel_cod_order_via_web(): void
    {
        // Accept order first via service so initial transaction exists
        $order = app(\App\Services\ActiveCodOrderService::class)->acceptCodOrder(
            workDay: $this->workDay,
            orderCode: '888',
            billAmount: 25000,
            merchantName: 'Warung Soto'
        );

        $this->assertEquals(75000, $this->shopeePay->fresh()->current_balance);

        $response = $this->post("/operational/cod-orders/{$order->id}/cancel", [
            'reason' => 'Resto tutup saat driver sampai di lokasi',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('cancelled', $order->fresh()->status);
        // Saldo ShopeePay dipulihkan utuh ke 100.000
        $this->assertEquals(100000, $this->shopeePay->fresh()->current_balance);
    }
}
