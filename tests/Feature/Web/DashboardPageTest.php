<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Account::create([
            'name' => 'BCA Digital',
            'type' => AccountType::BANK,
            'current_balance' => 500000,
            'initial_balance' => 500000,
        ]);

        Account::create([
            'name' => 'Cash Dompet',
            'type' => AccountType::CASH,
            'current_balance' => 150000,
            'initial_balance' => 150000,
        ]);

        Debt::create([
            'name' => 'SPayLater Cicilan HP',
            'platform' => 'spaylater',
            'borrower_name' => 'Self',
            'principal_amount' => 200000,
            'fee_amount' => 0,
            'total_obligation' => 200000,
            'tenor_months' => 3,
            'status' => 'active',
            'started_at' => now()->subMonth(),
        ]);
    }

    public function test_dashboard_page_loads_successfully_with_http_200(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('DRIVER FINANCE');
        $response->assertSee('Total Kekayaan Bersih (Net Worth)');
    }

    public function test_dashboard_displays_all_active_accounts_and_correct_net_worth(): void
    {
        // Net Worth = (500.000 + 150.000) - 200.000 = 450.000
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('BCA Digital');
        $response->assertSee('Cash Dompet');
        $response->assertSee('Rp 450.000');
    }

    public function test_dashboard_renders_unfinalized_banner_when_pending_days_exist(): void
    {
        $this->travelTo(now()->setHour(14));

        $yesterday = now()->subDay()->toDateString();
        WorkDay::create([
            'work_date' => $yesterday,
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Hari Kerja Belum Difinalisasi');
        $response->assertSee('Investigasi Sekarang');
    }

    public function test_dashboard_renders_voice_memo_intercom_fab_and_modal(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Intercom Helm');
        $response->assertSee('modal-voice-recorder');
        $response->assertSee('Kotak Hitam & Memo Suara Helm Hari Ini', false);
    }

    public function test_dashboard_displays_today_voice_memos_with_gps(): void
    {
        $todayWorkDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        \App\Models\VoiceMemo::create([
            'work_day_id' => $todayWorkDay->id,
            'file_path' => 'voice_memos/test_memo.webm',
            'duration_seconds' => 12,
            'latitude' => -7.9826,
            'longitude' => 112.6308,
            'recorded_at' => now(),
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('1 Rekaman');
        $response->assertSee('12s');
        $response->assertSee('maps?q=-7.9826,112.6308', false);
    }

    public function test_dashboard_renders_workday_not_opened_banner_when_no_active_shift(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Hari Kerja Belum Dibuka');
        $response->assertSee('Buka hari kerja untuk mulai mencatat target harian');
        $response->assertSee('Buka Hari Kerja Sekarang');
    }

    public function test_dashboard_does_not_display_expense_or_income_accounts_in_cards_or_init_modal(): void
    {
        $expense = Account::create([
            'name' => 'Beban Bensin Kendaraan',
            'type' => AccountType::EXPENSE,
            'current_balance' => 0,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $income = Account::create([
            'name' => 'Pendapatan Hub ShopeeFood',
            'type' => AccountType::INCOME,
            'current_balance' => 0,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        // Memastikan akun beban dan pendapatan tidak muncul di daftar kartu rekening maupun opsi atur saldo awal
        $response->assertDontSee('Beban Bensin Kendaraan (EXPENSE &bull; Saat ini: Rp 0)', false);
        $response->assertDontSee('Pendapatan Hub ShopeeFood (INCOME &bull; Saat ini: Rp 0)', false);
    }

    public function test_cannot_set_initial_balance_on_expense_account_via_web(): void
    {
        $expense = Account::create([
            'name' => 'Beban Parkir',
            'type' => AccountType::EXPENSE,
            'current_balance' => 0,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $response = $this->post('/operational/set-initial-balance', [
            'account_id' => $expense->id,
            'amount' => 50000,
        ]);

        $response->assertSessionHas('error', 'Hanya akun aset / wadah simpanan kas (Bank, E-Wallet, Kas) yang dapat memiliki saldo awal.');
        $this->assertEquals(0, $expense->fresh()->initial_balance);
        $this->assertEquals(0, $expense->fresh()->current_balance);
    }

    public function test_accept_cod_order_and_render_recent_transactions_on_dashboard_without_relation_error(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'current_balance' => 200000,
            'initial_balance' => 200000,
            'is_active' => true,
        ]);

        $todayWorkDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $postResponse = $this->post('/operational/cod-orders/accept', [
            'work_day_id' => $todayWorkDay->id,
            'order_code' => 'SPF-998877',
            'merchant_name' => 'Ayam Geprek Kak Rose',
            'bill_amount' => 35000,
            'rounded_bill_amount' => 35000,
        ]);

        $postResponse->assertSessionHas('success');

        // Mengakses dashboard setelah ada transaksi riil harus HTTP 200 tanpa RelationNotFoundException
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('20 Transaksi Terakhir');
        $dashboardResponse->assertSee('Talangan COD #SPF-998877');
    }

    public function test_checkpoint_modal_inputs_default_to_zero_and_not_contaminated_by_cod_bill_or_cash_tips(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $shopeePay = Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'current_balance' => 200000,
            'initial_balance' => 200000,
            'is_active' => true,
        ]);

        $cash = Account::create([
            'name' => 'Cash',
            'type' => AccountType::CASH,
            'current_balance' => 100000,
            'initial_balance' => 100000,
            'is_active' => true,
        ]);

        $todayWorkDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        // Simulasikan order COD senilai 17.800 dan customer membayar 19.000 cash (surplus tip 1.200)
        $this->post('/operational/cod-orders/accept', [
            'work_day_id' => $todayWorkDay->id,
            'order_code' => 'COD-17800',
            'merchant_name' => 'Nasi Padang',
            'bill_amount' => 17800,
            'rounded_bill_amount' => 18000,
        ]);

        $order = \App\Models\ActiveCodOrder::where('order_code', 'COD-17800')->firstOrFail();

        $this->post("/operational/cod-orders/{$order->id}/complete", [
            'payment_method' => 'cash',
            'received_amount' => 19000,
            'customer_gave_amount' => 19000,
            'change_returned' => 0,
            'settlement_account_id' => $cash->id,
        ]);

        // Buka Dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Verifikasi metrik controller:
        // Tagihan COD yang dilunasi adalah 17.800
        $response->assertViewHas('dayCodBillSettled', 17800);
        // Pendapatan reguler ShopeeFood TIDAK boleh terkontaminasi oleh tagihan makanan COD (tetap 0)
        $response->assertViewHas('dayRegularIncome', 0);
        // Tip COD masuk ke metrik tip hari ini
        $response->assertViewHas('dayTipIncome', 1200);

        // Verifikasi bahwa input di form modal Update Saldo Narik:
        // Tip dari pelanggan di ShopeeFood Driver TETAP 0 (bukan 1.200)
        $response->assertSee('id="recon-customer-tips"', false);
        $response->assertSee('id="recon-hub-income"', false);
        $response->assertSee('id="recon-completed-orders"', false);
        $response->assertDontSee('id="recon-customer-tips" \n value="1200"', false);
        $response->assertDontSee('1200 Pesanan Selesai');
    }
}
