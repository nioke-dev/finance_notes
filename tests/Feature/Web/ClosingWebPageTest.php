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
}
