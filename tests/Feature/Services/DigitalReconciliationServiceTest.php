<?php

namespace Tests\Feature\Services;

use App\Models\Account;
use App\Models\WorkDay;
use App\Services\DigitalReconciliationService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkDay $workDay;
    protected Account $goPayMerchant;
    protected Account $goPay;
    protected DigitalReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DefaultAccountsSeeder::class);

        $this->goPayMerchant = Account::where('name', 'GoPay Merchant')->firstOrFail();
        $this->goPayMerchant->update(['current_balance' => 0, 'initial_balance' => 0]);

        $this->goPay = Account::where('name', 'GoPay')->firstOrFail();
        $this->goPay->update(['current_balance' => 50000, 'initial_balance' => 50000]);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);

        $this->service = app(DigitalReconciliationService::class);
    }

    public function test_discovers_qris_surplus_and_credits_qris_income(): void
    {
        // GoPay Merchant ada saldo receh 3.000, GoPay ada 65.000 (surplus 15.000 dari tip nyusul)
        $res = $this->service->reconcileDigitalAccounts($this->workDay, [
            ['account_id' => $this->goPayMerchant->id, 'actual_balance' => 3000],
            ['account_id' => $this->goPay->id, 'actual_balance' => 65000],
        ]);

        $this->assertEquals(2, $res['adjusted_count']);
        $this->assertEquals(18000, $res['total_surplus']); // 3.000 + 15.000

        // Saldo akun ter-update
        $this->assertEquals(3000, $this->goPayMerchant->fresh()->current_balance);
        $this->assertEquals(65000, $this->goPay->fresh()->current_balance);

        // Akun Pendapatan QRIS bertambah 18.000
        $qrisIncome = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->firstOrFail();
        $this->assertEquals(18000, $qrisIncome->current_balance);
    }

    public function test_handles_bank_admin_fee_shortage(): void
    {
        $bca = Account::where('name', 'BCA')->firstOrFail();
        $bca->update(['current_balance' => 100000]);

        // M-banking BCA terpotong biaya admin bulanan 15.000 -> sisa 85.000
        $res = $this->service->reconcileDigitalAccounts($this->workDay, [
            ['account_id' => $bca->id, 'actual_balance' => 85000],
        ]);

        $this->assertEquals(1, $res['adjusted_count']);
        $this->assertEquals(15000, $res['total_shortage']);

        $this->assertEquals(85000, $bca->fresh()->current_balance);

        $adminExpense = Account::where('name', 'Beban Biaya Admin Transfer')->firstOrFail();
        $this->assertEquals(15000, $adminExpense->current_balance);
    }
}
