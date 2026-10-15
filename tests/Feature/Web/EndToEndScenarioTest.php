<?php

namespace Tests\Feature\Web;

use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\PaylaterProvider;
use App\Models\User;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Database\Seeders\PaylaterProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WorkDay $workDay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Driver Nurul Mustofa',
        ]);

        $this->actingAs($this->user);

        // Seed master accounts and paylater providers
        $this->seed(DefaultAccountsSeeder::class);
        $this->seed(PaylaterProviderSeeder::class);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);
    }

    /**
     * Skenario 1: Tampilan Portofolio & Cek Teks/Kata per Kata
     */
    public function test_scenario_1_wallets_page_renders_all_expected_words_and_cards(): void
    {
        $response = $this->get('/wallets');

        $response->assertStatus(200);

        // Header & navigasi
        $response->assertSee('Manajemen Wadah Finansial');
        $response->assertSee('Master Portofolio & Akun', false);
        $response->assertSee('Wadah Simpanan');
        $response->assertSee('Wadah PayLater');
        $response->assertSee('Catat Tagihan');

        // Ringkasan 4 Metrik Portofolio
        $response->assertSee('Total Saldo Aset Simpanan');
        $response->assertSee('Total Plafon PayLater');
        $response->assertSee('Total Kewajiban Utang');
        $response->assertSee('Kekayaan Bersih (Net Worth)');

        // 3 Bagian Utama
        $response->assertSee('Wadah Simpanan & Uang (Kas, Rekening Bank, E-Wallet)', false);
        $response->assertSee('Wadah Kredit & PayLater (Plafon Limit & Saldo Terpakai)', false);
        $response->assertSee('Daftar Tagihan & Cicilan Berjalan (PayLater & Pinjaman)', false);

        // PayLater default dari seeder
        $response->assertSee('SPayLater');
        $response->assertSee('GoPay Later');
        $response->assertSee('TikTok PayLater');
        $response->assertSee('Kredivo');
        $response->assertSee('Akulaku');
        $response->assertSee('Indodana');
    }

    /**
     * Skenario 2: Aturan Integritas Penghapusan vs Penutupan/Pengarsipan Wadah Simpanan
     */
    public function test_scenario_2_account_lifecycle_and_audit_protection(): void
    {
        // 1. Tambah Wadah Baru: Bank Jago (Saldo Awal Rp 500.000)
        $response = $this->post('/operational/account', [
            'name' => 'Bank Jago',
            'type' => 'bank',
            'initial_balance' => 500000,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $jago = Account::where('name', 'Bank Jago')->firstOrFail();
        $this->assertEquals(500000, $jago->current_balance);

        // 2. Coba Hapus Bank Jago saat saldo > 0 -> Ditolak!
        $deleteFailResponse = $this->delete('/operational/account/' . $jago->id);
        $deleteFailResponse->assertRedirect();
        $deleteFailResponse->assertSessionHas('error');
        $this->assertDatabaseHas('accounts', ['id' => $jago->id]);

        // 3. Pindahkan saldo Bank Jago ke Cash agar saldo jadi Rp 0
        $cash = Account::where('name', 'Cash')->firstOrFail();
        $this->post('/operational/transfer', [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $jago->id,
            'destination_account_id' => $cash->id,
            'amount' => 500000,
            'admin_fee' => 0,
            'note' => 'Pindahkan saldo sebelum tutup akun',
        ]);
        $jago->refresh();
        $this->assertEquals(0, $jago->current_balance);
        $this->assertTrue($jago->ledgerEntries()->exists());

        // 4. Coba Hapus Bank Jago yang sudah memiliki riwayat mutasi transaksi -> Ditolak!
        $deleteProtectedResponse = $this->delete('/operational/account/' . $jago->id);
        $deleteProtectedResponse->assertRedirect();
        $deleteProtectedResponse->assertSessionHas('error');
        $this->assertDatabaseHas('accounts', ['id' => $jago->id]);

        // 5. Tutup / Arsipkan Bank Jago
        $toggleCloseResponse = $this->post('/operational/account/' . $jago->id . '/toggle-active');
        $toggleCloseResponse->assertRedirect();
        $toggleCloseResponse->assertSessionHas('success');
        $this->assertFalse($jago->fresh()->is_active);

        // 6. Buka kembali Bank Jago
        $toggleOpenResponse = $this->post('/operational/account/' . $jago->id . '/toggle-active');
        $toggleOpenResponse->assertRedirect();
        $toggleOpenResponse->assertSessionHas('success');
        $this->assertTrue($jago->fresh()->is_active);

        // 7. Buat akun salah ketik yang belum pernah ada transaksi -> Hapus permanen berhasil
        $typoAcc = Account::create([
            'name' => 'Salah Ketik BCA 2',
            'type' => 'bank',
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);
        $deleteSuccessResponse = $this->delete('/operational/account/' . $typoAcc->id);
        $deleteSuccessResponse->assertRedirect();
        $deleteSuccessResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('accounts', ['id' => $typoAcc->id]);
    }

    /**
     * Skenario 3: Penambahan & Pengelolaan Wadah PayLater Baru
     */
    public function test_scenario_3_paylater_provider_creation_limit_update_and_deletion(): void
    {
        // 1. Tambah Wadah PayLater baru: "Blibli PayLater"
        $response = $this->post('/operational/paylater-providers', [
            'name' => 'Blibli PayLater',
            'credit_limit' => 3000000,
            'icon' => '🛍️',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $blibli = PaylaterProvider::where('slug', 'blibli_paylater')->firstOrFail();
        $this->assertEquals(3000000, $blibli->credit_limit);
        $this->assertEquals(0, $blibli->used_credit);
        $this->assertEquals(3000000, $blibli->available_credit);

        // 2. Update limit plafon kredit menjadi Rp 4.500.000
        $updateLimitResponse = $this->post('/operational/paylater-providers/' . $blibli->id . '/limit', [
            'credit_limit' => 4500000,
        ]);
        $updateLimitResponse->assertRedirect();
        $updateLimitResponse->assertSessionHas('success');
        $this->assertEquals(4500000, $blibli->fresh()->credit_limit);

        // 3. Hapus wadah PayLater kosong yang belum pernah ada transaksi
        $deleteResponse = $this->delete('/operational/paylater-providers/' . $blibli->id);
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('paylater_providers', ['id' => $blibli->id]);
    }

    /**
     * Skenario 4: Pembayaran Belanja Menggunakan Wadah PayLater & Integrasi Finansial
     */
    public function test_scenario_4_spending_via_paylater_in_expense_modal(): void
    {
        $spaylater = PaylaterProvider::where('slug', 'spaylater')->firstOrFail();
        $spaylater->update(['credit_limit' => 2000000]);
        $initialAvailable = $spaylater->fresh()->available_credit;

        $cashBefore = Account::where('name', 'Cash')->firstOrFail()->current_balance;

        // Catat pengeluaran belanja via SPayLater (Tenor 3 Bulan)
        $expenseResponse = $this->post('/operational/personal-expense', [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => 'paylater_spaylater',
            'amount' => 300000,
            'description' => 'Beli Sepatu Kerja Waterproof',
            'tenor_months' => 3,
        ]);

        $expenseResponse->assertRedirect();
        $expenseResponse->assertSessionHas('success');

        // A. Uang fisik di kas / bank sama sekali TIDAK berkurang
        $cashAfter = Account::where('name', 'Cash')->firstOrFail()->current_balance;
        $this->assertEquals($cashBefore, $cashAfter);

        // B. Terbuat 1 data Debt aktif
        $debt = Debt::where('platform', 'spaylater')->latest()->first();
        $this->assertNotNull($debt);
        $this->assertEquals('Belanja PayLater: Beli Sepatu Kerja Waterproof', $debt->name);
        $this->assertEquals(300000, $debt->total_obligation);
        $this->assertEquals(3, $debt->tenor_months);
        $this->assertEquals('active', $debt->status);

        // C. Terbuat 3 jadwal cicilan bulanan masing-masing Rp 100.000
        $installments = $debt->installments()->orderBy('installment_number')->get();
        $this->assertCount(3, $installments);
        $this->assertEquals(100000, $installments[0]->amount_due);
        $this->assertEquals(100000, $installments[1]->amount_due);
        $this->assertEquals(100000, $installments[2]->amount_due);

        // D. Limit SPayLater terupdate secara presisi
        $spaylater->refresh();
        $this->assertEquals(300000, $spaylater->used_credit);
        $this->assertEquals($initialAvailable - 300000, $spaylater->available_credit);

        // E. Coba hapus SPayLater yang sekarang punya transaksi -> Dilarang!
        $deleteAttempt = $this->delete('/operational/paylater-providers/' . $spaylater->id);
        $deleteAttempt->assertRedirect();
        $deleteAttempt->assertSessionHas('error');
        $this->assertDatabaseHas('paylater_providers', ['id' => $spaylater->id]);

        // F. Coba arsipkan SPayLater yang masih ada tagihan berjalan -> Dilarang!
        $toggleAttempt = $this->post('/operational/paylater-providers/' . $spaylater->id . '/toggle-active');
        $toggleAttempt->assertRedirect();
        $toggleAttempt->assertSessionHas('error');
        $this->assertTrue($spaylater->fresh()->is_active);

        // G. Bayar seluruh cicilan sampai lunas
        $bca = Account::where('name', 'BCA')->firstOrFail();
        foreach ($installments as $inst) {
            $payResp = $this->post('/operational/pay-installment', [
                'work_day_id' => $this->workDay->id,
                'installment_id' => $inst->id,
                'payment_account_id' => $bca->id,
                'actual_paid' => 100000,
            ]);
            $payResp->assertRedirect();
            $payResp->assertSessionHas('success');
        }

        $debt->refresh();
        $this->assertEquals('paid_off', $debt->status);
        $this->assertTrue($debt->isPaidOff());
        $this->assertEquals(0, $debt->remaining_amount);

        // H. Limit terpakai kembali ke Rp 0
        $spaylater->refresh();
        $this->assertEquals(0, $spaylater->used_credit);
        $this->assertEquals($spaylater->credit_limit, $spaylater->available_credit);

        // I. Sekarang tutup/arsipkan SPayLater setelah lunas -> Berhasil!
        $toggleSuccess = $this->post('/operational/paylater-providers/' . $spaylater->id . '/toggle-active');
        $toggleSuccess->assertRedirect();
        $toggleSuccess->assertSessionHas('success');
        $this->assertFalse($spaylater->fresh()->is_active);
    }

    /**
     * Skenario 5: Pencatatan Tagihan Berjalan (Existing Debt) & Proteksi Hapus
     */
    public function test_scenario_5_existing_debt_recording_and_deletion_protection(): void
    {
        // 1. Catat tagihan berjalan baru: TikTok PayLater (Tenor 2 Bulan, Rp 600.000)
        $response = $this->post('/operational/existing-debt', [
            'name' => 'Cicilan HP TikTok',
            'platform' => 'tiktok_paylater',
            'total_obligation' => 600000,
            'tenor_months' => 2,
            'due_date' => now()->addMonth()->endOfMonth()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $debt = Debt::where('platform', 'tiktok_paylater')->firstOrFail();
        $this->assertEquals(600000, $debt->total_obligation);
        $this->assertEquals(2, $debt->installments()->count());

        // 2. Karena belum ada cicilan yang dibayar, hapus tagihan yang salah catat -> Berhasil
        $deleteResp = $this->delete('/operational/debts/' . $debt->id);
        $deleteResp->assertRedirect();
        $deleteResp->assertSessionHas('success');
        $this->assertNull(Debt::find($debt->id));
        $this->assertDatabaseMissing('debt_installments', ['debt_id' => $debt->id]);

        // 3. Catat ulang dan bayar cicilan pertama
        $this->post('/operational/existing-debt', [
            'name' => 'Cicilan HP TikTok Baru',
            'platform' => 'tiktok_paylater',
            'total_obligation' => 600000,
            'tenor_months' => 2,
            'due_date' => now()->addMonth()->endOfMonth()->toDateString(),
        ]);
        $debt2 = Debt::where('platform', 'tiktok_paylater')->latest()->firstOrFail();
        $firstInst = $debt2->installments()->orderBy('installment_number')->firstOrFail();

        $bca = Account::where('name', 'BCA')->firstOrFail();
        $this->post('/operational/pay-installment', [
            'work_day_id' => $this->workDay->id,
            'installment_id' => $firstInst->id,
            'payment_account_id' => $bca->id,
            'actual_paid' => 300000,
        ]);

        // 4. Coba hapus tagihan yang sudah dicicil -> Dilarang demi audit trail!
        $deleteBlocked = $this->delete('/operational/debts/' . $debt2->id);
        $deleteBlocked->assertRedirect();
        $deleteBlocked->assertSessionHas('error');
        $this->assertNotNull(Debt::find($debt2->id));

        // 5. Catat tagihan dengan custom platform manual (misal: Mega PayLater)
        $customResp = $this->post('/operational/existing-debt', [
            'name' => 'Belanja Meja Belajar',
            'platform' => 'other',
            'custom_platform' => 'Mega PayLater',
            'total_obligation' => 450000,
            'tenor_months' => 1,
            'due_date' => now()->addWeeks(3)->toDateString(),
        ]);
        $customResp->assertRedirect();
        $customResp->assertSessionHas('success');

        $customDebt = Debt::where('platform', 'mega_paylater')->first();
        $this->assertNotNull($customDebt);
        $this->assertEquals(450000, $customDebt->total_obligation);
    }
}
