<?php

namespace Tests\Feature\Web;

use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_debts_index_displays_active_debts_and_summary_totals(): void
    {
        // 1. Setup akun pembayaran
        Account::create([
            'name' => 'BCA',
            'type' => 'bank',
            'current_balance' => 1000000,
            'is_active' => true,
        ]);

        // 2. Setup Utang Saya (PayLater / Bank)
        $myDebt = Debt::create([
            'name' => 'SPayLater - Beli Jas Hujan',
            'platform' => 'spaylater',
            'borrower_name' => null,
            'principal_amount' => 500000,
            'fee_amount' => 0,
            'total_obligation' => 500000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        DebtInstallment::create([
            'debt_id' => $myDebt->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(5)->toDateString(),
            'amount_due' => 500000,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        // 3. Setup Piutang Teman
        $friendDebt = Debt::create([
            'name' => 'Talangan Bensin Teman',
            'platform' => 'person',
            'borrower_name' => 'Rofik',
            'principal_amount' => 100000,
            'fee_amount' => 0,
            'total_obligation' => 100000,
            'tenor_months' => 1,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        // 4. Akses GET /debts
        $response = $this->get('/debts');

        // 5. Verifikasi Response
        $response->assertStatus(200);
        $response->assertViewIs('pages.debts.index');
        $response->assertSee('SPayLater - Beli Jas Hujan');
        $response->assertSee('Rofik');
        $response->assertSee('500.000');
        $response->assertSee('100.000');
        $response->assertSee('Jadwal Jatuh Tempo Terdekat');
    }
}
