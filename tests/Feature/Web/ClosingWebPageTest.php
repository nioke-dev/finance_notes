<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosingWebPageTest extends TestCase
{
    use RefreshDatabase;

    protected WorkDay $workDay;

    protected function setUp(): void
    {
        parent::setUp();

        Account::create([
            'name' => 'Cash',
            'type' => AccountType::CASH,
            'current_balance' => 150000,
            'initial_balance' => 150000,
        ]);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);
    }

    public function test_closing_web_page_loads_successfully_with_http_200(): void
    {
        $response = $this->get('/closing/' . $this->workDay->id);

        $response->assertStatus(200);
        $response->assertSee('Audit & Closing Shift Narik', false);
        $response->assertSee('Denominasi Uang Tunai Fisik');
        $response->assertSee('Simpan Draft / Tutup Nanti');
    }

    public function test_closing_page_renders_denomination_inputs(): void
    {
        $response = $this->get('/closing/' . $this->workDay->id);

        $response->assertStatus(200);
        $response->assertSee('name="bill_100000"', false);
        $response->assertSee('name="bill_50000"', false);
        $response->assertSee('name="coin_1000"', false);
        $response->assertSee('name="coin_500"', false);
    }

    public function test_finalize_button_disabled_when_discrepancy_exists(): void
    {
        // Saldo kas buku = 150.000, fisik denomination = 0 -> selisih -150.000
        $response = $this->get('/closing/' . $this->workDay->id);

        $response->assertStatus(200);
        $response->assertSee('disabled', false);
        $response->assertSee('Masih Ada Selisih');
        $response->assertSee('cursor-not-allowed', false);
    }

    public function test_finalize_button_enabled_when_balanced(): void
    {
        // Tambahkan record cash_denominations yang pas 150.000
        CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_100000' => 1,
            'bill_50000' => 1,
            'total_physical_amount' => 150000,
        ]);

        $response = $this->get('/closing/' . $this->workDay->id);

        $response->assertStatus(200);
        $response->assertSee('Saldo Seimbang Sempurna');
        $response->assertDontSee('cursor-not-allowed');
    }

    public function test_closing_shows_adjust_discrepancy_button_when_discrepancy_exists(): void
    {
        // Saldo buku 150.000, fisik 0 -> selisih -150.000
        $response = $this->get('/closing/' . $this->workDay->id);

        $response->assertStatus(200);
        $response->assertSee('Sesuaikan Kas Hilang / Lebih Tak Terlacak');
    }

    public function test_user_can_adjust_cash_discrepancy_via_web_post(): void
    {
        // Set fisik kas 148.000 (selisih -2.000)
        CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_100000' => 1,
            'bill_20000' => 2,
            'bill_5000' => 1,
            'bill_2000' => 1,
            'bill_1000' => 1,
            'total_physical_amount' => 148000,
        ]);

        $response = $this->post('/closing/' . $this->workDay->id . '/adjust-discrepancy', [
            'reason' => 'Uang receh Rp2.000 tercecer saat bayar parkir siang tadi',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Saldo kas buku turun dari 150.000 menjadi 148.000
        $cash = Account::where('name', 'Cash')->first();
        $this->assertEquals(148000, $cash->current_balance);
    }

    public function test_closing_renders_shopeefood_and_parking_vs_tip_evaluation(): void
    {
        // Setup akun ShopeePay, GoPay, dan parkir
        $shopeePay = Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        $gopay = Account::create([
            'name' => 'GoPay',
            'type' => AccountType::EWALLET,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        $parkingExpenseAccount = Account::create([
            'name' => 'Beban Parkir',
            'type' => AccountType::EXPENSE,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        // Catat pengeluaran parkir
        $parkingService = app(\App\Services\QuickParkingService::class);
        $parkingService->recordParking(
            workDay: $this->workDay,
            amount: 2000,
            restaurantName: 'Geprek Bensu'
        );

        $response = $this->get('/closing/' . $this->workDay->id);

        $response->assertStatus(200);
        $response->assertSee('Evaluasi ShopeeFood Driver & Komparasi Parkir vs Tip', false);
        $response->assertSee('Komparasi Parkir vs Tip & Pendapatan QRIS', false);
        $response->assertSee('Total Beban Parkir');
        $response->assertSee('Rp 2.000');
        $response->assertSee('Simpan & Selaraskan Saldo Shift Ini', false);
    }

    public function test_closing_workflow_with_shopeefood_reconciliation_and_finalization(): void
    {
        $shopeePay = Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        $gopay = Account::create([
            'name' => 'GoPay',
            'type' => AccountType::EWALLET,
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);

        // Fisik uang di dompet cocok dengan saldo Cash (150.000)
        CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_100000' => 1,
            'bill_50000' => 1,
            'total_physical_amount' => 150000,
        ]);

        // Simpan rekonsiliasi ShopeeFood dari closing page: Hub Rp 76.000, Tip Rp 5.000
        $payload = [
            'work_day_id' => $this->workDay->id,
            'completed_orders' => 12,
            'hub_income' => 76000,
            'hub_bonus' => 0,
            'customer_tips' => 5000,
            'balances' => [
                [
                    'account_id' => $shopeePay->id,
                    'actual_balance' => 76000, // Surplus +Rp76.000 masuk ShopeePay
                ],
                [
                    'account_id' => $gopay->id,
                    'actual_balance' => 5000,  // Surplus +Rp5.000 masuk GoPay
                ],
            ],
        ];

        $reconResponse = $this->post('/operational/digital-reconciliation', $payload);
        $reconResponse->assertRedirect();
        $reconResponse->assertSessionHas('success');

        // ShopeePay sekarang 76.000, GoPay 5.000
        $this->assertEquals(76000, $shopeePay->fresh()->current_balance);
        $this->assertEquals(5000, $gopay->fresh()->current_balance);

        // Akses closing page kembali: Saldo sekarang seimbang sempurna!
        $closingResponse = $this->get('/closing/' . $this->workDay->id);
        $closingResponse->assertStatus(200);
        $closingResponse->assertSee('Saldo Seimbang Sempurna');
        $closingResponse->assertDontSee('cursor-not-allowed');

        // Finalisasi shift berhasil!
        $finalizeResponse = $this->post('/closing/' . $this->workDay->id . '/finalize', [
            'closing_notes' => 'Shift 16 Oktober sukses ditutup dengan 12 pesanan selesai.',
        ]);

        $finalizeResponse->assertRedirect('/dashboard');
        $this->assertEquals(WorkDay::STATUS_FINAL, $this->workDay->fresh()->status);
    }
}
