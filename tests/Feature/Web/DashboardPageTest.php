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
        $response->assertSee('DRIVER FINANCES');
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
}
