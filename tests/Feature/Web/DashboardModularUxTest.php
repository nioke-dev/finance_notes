<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\FinancialGoal;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardModularUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Account::create([
            'name' => 'Cash',
            'type' => AccountType::CASH,
            'current_balance' => 888000,
            'initial_balance' => 888000,
            'is_active' => true,
        ]);

        Account::create([
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'current_balance' => 805574,
            'initial_balance' => 805574,
            'is_active' => true,
        ]);

        Account::create([
            'name' => 'ShopeePay',
            'type' => AccountType::EWALLET,
            'current_balance' => 64326,
            'initial_balance' => 64326,
            'is_active' => true,
        ]);

        Account::create([
            'name' => 'GoPay',
            'type' => AccountType::EWALLET,
            'current_balance' => 27288,
            'initial_balance' => 27288,
            'is_active' => true,
        ]);

        Account::create([
            'name' => 'Beban Parkir',
            'type' => AccountType::EXPENSE,
            'current_balance' => 0,
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        Debt::create([
            'name' => 'Makan All You Can Eat Kokari Malang',
            'platform' => 'personal_loan',
            'borrower_name' => 'Teman',
            'principal_amount' => 150000,
            'fee_amount' => 0,
            'total_obligation' => 150000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now(),
        ]);

        FinancialGoal::create([
            'name' => 'Sewa Hotel Wisuda (30-31 Okt)',
            'target_amount' => 250000,
            'current_amount' => 0,
            'target_date' => '2026-10-30',
            'category' => 'survival_needs',
            'priority_level' => 1,
            'status' => 'active',
        ]);
    }

    public function test_dashboard_renders_desktop_view_navigation_and_all_four_views(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="desktop-view-nav"', false);
        $response->assertSee('id="view-home"', false);
        $response->assertSee('id="view-ops"', false);
        $response->assertSee('id="view-wallets"', false);
        $response->assertSee('id="view-analytics"', false);

        // Assert view navigation titles
        $response->assertSee('Ringkasan & Misi 30 Okt', false);
        $response->assertSee('Jalanan & Fast Ops', false);
        $response->assertSee('Dompet & Utang', false);
        $response->assertSee('Analitik & Riwayat', false);
    }

    public function test_dashboard_renders_sticky_mobile_bottom_navigation_bar(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="mobile-bottom-nav"', false);
        $response->assertSee('data-mobile-target-view="view-home"', false);
        $response->assertSee('data-mobile-target-view="view-ops"', false);
        $response->assertSee('data-mobile-target-view="view-wallets"', false);
        $response->assertSee('data-mobile-target-view="view-analytics"', false);
    }

    public function test_dashboard_renders_debt_freedom_30_october_runway_hero_widget(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="widget-debt-freedom-runway"', false);
        $response->assertSee('Misi Bebas Utang 30 Oktober 2026');
        $response->assertSee('5.5 Hari Kerja Tersisa');
        $response->assertSee('Aset Likuid Terkumpul');
        $response->assertSee('Defisit Sisa (Gap)');
        $response->assertSee('Target Bersih Narik');
        $response->assertSee('Kokari Rp 150k');
        $response->assertSee('Hotel Wisuda Rp 250k');
    }

    public function test_dashboard_renders_floating_sticky_quick_action_dock_for_drivers(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Aksi Cepat Lapangan (Zero-Scroll)');
        $response->assertSee('Parkir 1k');
        $response->assertSee('Parkir 2k');
        $response->assertSee('Parkir Resto (Gratis)');
        $response->assertSee('+ Tip Pelanggan');
        $response->assertSee('Talangan COD');
        $response->assertSee('Isi Bensin');
        $response->assertSee('Memo Helm');
        $response->assertSee('Update Saldo');
    }

    public function test_driver_can_submit_one_tap_parking_instantly(): void
    {
        $workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $cash = Account::where('name', 'Cash')->firstOrFail();

        // 1-Tap Parkir 2.000
        $response = $this->post('/operational/parking', [
            'work_day_id' => $workDay->id,
            'amount' => 2000,
            'source_account_id' => $cash->id,
            'vehicle_type' => 'motor',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(886000, $cash->fresh()->current_balance);

        // 1-Tap Parkir Resto Gratis
        $responseGratis = $this->post('/operational/parking', [
            'work_day_id' => $workDay->id,
            'amount' => 0,
            'is_covered_by_resto' => 1,
        ]);

        $responseGratis->assertSessionHas('success');
    }
}
