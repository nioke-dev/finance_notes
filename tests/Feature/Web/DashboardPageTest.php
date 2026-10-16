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
}
