<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\ActiveCodOrder;
use App\Models\AuditLog;
use App\Models\WorkDay;
use App\Services\ActiveCodOrderService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveCodOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkDay $workDay;
    protected Account $shopeePay;
    protected Account $cash;
    protected Account $goPay;
    protected ActiveCodOrderService $service;

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

        $this->service = app(ActiveCodOrderService::class);
    }

    public function test_accept_cod_order_deducts_shopee_pay_and_creates_on_delivery_record(): void
    {
        $order = $this->service->acceptCodOrder(
            workDay: $this->workDay,
            orderCode: '633',
            billAmount: 51700,
            merchantName: 'Geprek Kak Rose Dinoyo',
            latitude: -7.9456,
            longitude: 112.6145
        );

        $this->assertEquals('633', $order->order_code);
        $this->assertEquals('on_delivery', $order->status);
        $this->assertEquals(51700, $order->bill_amount);
        $this->assertEquals(52000, $order->rounded_bill_amount); // Otomatis pembulatan de facto

        // ShopeePay berkurang: 100.000 - 51.700 = 48.300
        $this->assertEquals(48300, $this->shopeePay->fresh()->current_balance);

        // Talangan COD bertambah: 51.700
        $talangan = Account::where('name', 'Talangan Pesanan COD')->firstOrFail();
        $this->assertEquals(51700, $talangan->current_balance);

        // Audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ActiveCodOrder::class,
            'auditable_id' => $order->id,
            'action' => 'cod_order_accepted',
        ]);
    }

    public function test_complete_cod_order_cash_with_tip_restores_talangan_and_credits_cash(): void
    {
        $order = $this->service->acceptCodOrder(
            workDay: $this->workDay,
            orderCode: '633',
            billAmount: 51700,
            merchantName: 'Geprek Kak Rose Dinoyo'
        );

        // Di lokasi customer: customer ngasih 100k, minta kembalian 20k -> uang masuk kantong: 80.000
        $completedOrder = $this->service->completeCodOrder(
            order: $order,
            receivedAmount: 80000,
            paymentMethod: 'cash',
            customerGaveAmount: 100000,
            changeReturned: 20000,
            parkingReimbursed: 0
        );

        $this->assertEquals('delivered', $completedOrder->fresh()->status);
        $this->assertEquals(80000, $completedOrder->received_amount);
        $this->assertEquals(28300, $completedOrder->tip_amount); // 80.000 - 51.700 = 28.300

        // Kas bertambah: 150.000 + 80.000 = 230.000
        $this->assertEquals(230000, $this->cash->fresh()->current_balance);

        // Talangan COD impas kembali ke 0
        $talangan = Account::where('name', 'Talangan Pesanan COD')->firstOrFail();
        $this->assertEquals(0, $talangan->fresh()->current_balance);

        // Akun Tip menerima Rp28.300
        $tipAccount = Account::where('name', 'Tip Cash / Surplus COD')->firstOrFail();
        $this->assertEquals(28300, $tipAccount->current_balance);
    }

    public function test_complete_cod_order_with_qris_and_parking_reimbursement(): void
    {
        // Catat dulu ada beban parkir Rp2.000 sebelumnya
        $parkingExpense = Account::where('name', 'Beban Parkir')->firstOrFail();
        $parkingExpense->update(['current_balance' => 2000]);

        $order = $this->service->acceptCodOrder(
            workDay: $this->workDay,
            orderCode: '385',
            billAmount: 34200,
            merchantName: 'Mie Gacoan Sulfat'
        );

        // Customer bayar via QRIS ke GoPay Rp40.000 (dilebihkan untuk ganti parkir Rp2.000)
        $completedOrder = $this->service->completeCodOrder(
            order: $order,
            receivedAmount: 40000,
            paymentMethod: 'qris',
            settlementAccountId: $this->goPay->id,
            parkingReimbursed: 2000
        );

        $this->assertEquals('delivered', $completedOrder->fresh()->status);
        $this->assertEquals(2000, $completedOrder->parking_reimbursed);
        // Surplus total 40.000 - 34.200 = 5.800. Dikurangi parkir 2.000 = tip 3.800
        $this->assertEquals(3800, $completedOrder->tip_amount);

        // GoPay bertambah Rp40.000: 50.000 + 40.000 = 90.000
        $this->assertEquals(90000, $this->goPay->fresh()->current_balance);

        // Beban parkir berkurang Rp2.000 (teroffset): 2000 - 2000 = 0
        $this->assertEquals(0, $parkingExpense->fresh()->current_balance);

        // Pendapatan QRIS Tip menerima Rp3.800
        $qrisTipAcc = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->firstOrFail();
        $this->assertEquals(3800, $qrisTipAcc->current_balance);
    }

    public function test_cancel_cod_order_reverses_initial_shopeepay_deduction(): void
    {
        $order = $this->service->acceptCodOrder(
            workDay: $this->workDay,
            orderCode: '999',
            billAmount: 30000,
            merchantName: 'Resto Tutup'
        );

        $this->assertEquals(70000, $this->shopeePay->fresh()->current_balance);

        $cancelledOrder = $this->service->cancelCodOrder(
            order: $order,
            reason: 'Resto tutup saat driver sampai di lokasi'
        );

        $this->assertEquals('cancelled', $cancelledOrder->fresh()->status);

        // ShopeePay harus kembali utuh ke 100.000
        $this->assertEquals(100000, $this->shopeePay->fresh()->current_balance);

        // Talangan COD harus kembali ke 0
        $talangan = Account::where('name', 'Talangan Pesanan COD')->firstOrFail();
        $this->assertEquals(0, $talangan->fresh()->current_balance);
    }
}
