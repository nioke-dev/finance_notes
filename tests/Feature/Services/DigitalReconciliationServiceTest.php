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

    public function test_recognizes_gopay_merchant_auto_settlement_to_gopay(): void
    {
        // Kondisi awal (seperti di checkpoint sebelumnya):
        // GoPay Merchant = 4.000, GoPay = 20.000
        $this->goPayMerchant->update(['current_balance' => 4000]);
        $this->goPay->update(['current_balance' => 20000]);

        // Di lapangan, tips QRIS masuk sehingga GoPay Merchant sempat tembus > 10.000,
        // lalu otomatis cair (auto-settle) ke GoPay reguler.
        // Saldo akhir di HP driver:
        // GoPay Merchant = 3.000 (turun 1.000 dari saldo tercatat 4.000 karena tersapu pencairan)
        // GoPay = 30.000 (naik 10.000: 1.000 dari pencairan Merchant + 9.000 tip QRIS baru)
        $res = $this->service->reconcileDigitalAccounts($this->workDay, [
            ['account_id' => $this->goPayMerchant->id, 'actual_balance' => 3000],
            ['account_id' => $this->goPay->id, 'actual_balance' => 30000],
        ]);

        // Verifikasi tidak ada selisih kurang / biaya admin yang keliru!
        $this->assertEquals(0, $res['total_shortage'], 'Penurunan GoPay Merchant TIDAK boleh dicatat sebagai biaya admin');
        $this->assertEquals(1000, $res['merchant_settled'], 'Pencairan GoPay Merchant 1.000 wajib terdeteksi');
        $this->assertEquals(9000, $res['net_qris_tip'], 'Sisa kenaikan GoPay 9.000 diakui sebagai Tip QRIS Murni');

        // Saldo akun ter-update ke angka riil
        $this->assertEquals(3000, $this->goPayMerchant->fresh()->current_balance);
        $this->assertEquals(30000, $this->goPay->fresh()->current_balance);

        // Pastikan tidak ada akun Beban Biaya Admin yang bertambah
        $adminExpense = Account::where('name', 'Beban Biaya Admin Transfer')->first();
        $this->assertTrue(!$adminExpense || $adminExpense->current_balance === 0);
    }

    public function test_handles_gopay_merchant_residual_transit_drop_without_shortage(): void
    {
        // Kasus: GoPay sudah berada di saldo akhir, namun GoPay Merchant baru disinkronkan sekarang
        $this->goPayMerchant->update(['current_balance' => 4000]);
        $this->goPay->update(['current_balance' => 20000]);

        $res = $this->service->reconcileDigitalAccounts($this->workDay, [
            ['account_id' => $this->goPayMerchant->id, 'actual_balance' => 3000],
            ['account_id' => $this->goPay->id, 'actual_balance' => 20000],
        ]);

        $this->assertEquals(0, $res['total_shortage'], 'Penurunan GoPay Merchant tetap tidak boleh menjadi biaya admin');
        $this->assertEquals(1000, $res['merchant_settled']);
        $this->assertEquals(3000, $this->goPayMerchant->fresh()->current_balance);
        $this->assertEquals(20000, $this->goPay->fresh()->current_balance);
    }
}
