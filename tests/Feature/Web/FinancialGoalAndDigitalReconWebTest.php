<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\FinancialGoal;
use App\Models\User;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialGoalAndDigitalReconWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WorkDay $workDay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DefaultAccountsSeeder::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
            'day_type' => WorkDay::TYPE_ON_BID,
            'opened_at' => now(),
        ]);
    }

    public function test_dashboard_renders_safe_to_spend_and_goals(): void
    {
        FinancialGoal::create([
            'name' => 'Ganti Oli Mesin Honda',
            'category' => 'work_essential',
            'priority_level' => 3,
            'target_amount' => 55000,
            'allocated_amount' => 20000,
            'status' => 'active',
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Safe-to-Spend (Uang Aman Jajan)');
        $response->assertSee('Ganti Oli Mesin Honda');
        $response->assertSee('Level 3: Nyawa Narik');
        $response->assertSee('4 Elemen Saldo Uang Narik Driver');
        $response->assertSee('ShopeeFood Driver Hari Ini');
    }

    public function test_can_create_new_financial_goal(): void
    {
        $payload = [
            'name' => 'Beras 5kg & Telur 1kg',
            'category' => 'pangan',
            'priority_level' => 1,
            'target_amount' => 110000,
            'target_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Kebutuhan pokok makan di kos',
        ];

        $response = $this->post('/operational/goals', $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('financial_goals', [
            'name' => 'Beras 5kg & Telur 1kg',
            'category' => 'pangan',
            'priority_level' => 1,
            'target_amount' => 110000,
            'allocated_amount' => 0,
            'status' => 'active',
        ]);
    }

    public function test_can_allocate_funds_to_goal_step_by_step(): void
    {
        $goal = FinancialGoal::create([
            'name' => 'Phone Holder Motor Robot',
            'category' => 'work_essential',
            'priority_level' => 3,
            'target_amount' => 75000,
            'allocated_amount' => 10000,
            'status' => 'active',
        ]);

        // Alokasi cepat +20k
        $response = $this->post("/operational/goals/{$goal->id}/allocate", [
            'amount' => 20000,
            'notes' => 'Dari tip sore orderan martabak',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(30000, $goal->fresh()->allocated_amount);
    }

    public function test_can_achieve_goal_and_record_real_expense(): void
    {
        $cashAcc = Account::where('name', 'Cash')->firstOrFail();
        $cashAcc->update(['current_balance' => 150000]);

        $goal = FinancialGoal::create([
            'name' => 'Ganti Oli Mesin Honda MPX2',
            'category' => 'work_essential',
            'priority_level' => 3,
            'target_amount' => 55000,
            'allocated_amount' => 55000,
            'status' => 'active',
        ]);

        // Realisasikan belanja di bengkel seharga Rp53.000 riil di nota
        $response = $this->post("/operational/goals/{$goal->id}/achieve", [
            'account_id' => $cashAcc->id,
            'actual_spent' => 53000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Target status berubah jadi achieved
        $freshGoal = $goal->fresh();
        $this->assertEquals('achieved', $freshGoal->status);
        $this->assertEquals(53000, $freshGoal->allocated_amount);

        // Saldo kas dompet berkurang sebesar 53.000 (150.000 - 53.000 = 97.000)
        $this->assertEquals(97000, $cashAcc->fresh()->current_balance);
    }

    public function test_can_reconcile_digital_balances_and_detect_qris_surplus(): void
    {
        $gopayMerchant = Account::where('name', 'GoPay Merchant')->firstOrFail();
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();

        $gopayMerchant->update(['current_balance' => 10000]);
        $shopeePay->update(['current_balance' => 25000]);

        // Snapshot malam hari:
        // Di layar HP GoPay Merchant ada Rp15.000 (ada surplus tip QRIS +5.000 yang masuk belakangan)
        // Di layar HP ShopeePay ada Rp25.000 (cocok)
        $payload = [
            'work_day_id' => $this->workDay->id,
            'balances' => [
                [
                    'account_id' => $gopayMerchant->id,
                    'actual_balance' => 15000,
                ],
                [
                    'account_id' => $shopeePay->id,
                    'actual_balance' => 25000,
                ],
            ],
        ];

        $response = $this->post('/operational/digital-reconciliation', $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Saldo GoPay Merchant otomatis naik menjadi 15.000
        $this->assertEquals(15000, $gopayMerchant->fresh()->current_balance);

        // Tercatat transaksi pendapatan surplus QRIS digital
        $this->assertDatabaseHas('transactions', [
            'work_day_id' => $this->workDay->id,
            'type' => 'income',
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'amount' => 5000,
            'entry_type' => 'credit',
        ]);

        $qrisIncome = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->firstOrFail();
        $this->assertEquals(5000, $qrisIncome->current_balance);
    }

    public function test_can_save_driver_snapshot_checkpoint_with_cash_and_compare_on_dashboard(): void
    {
        $cashAcc = Account::where('name', 'Cash')->firstOrFail();
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $cashAcc->update(['current_balance' => 50000]);
        $shopeePay->update(['current_balance' => 80000]);

        $payload = [
            'work_day_id' => $this->workDay->id,
            'recorded_at' => now()->format('Y-m-d H:i:s'),
            'completed_orders' => 10,
            'delivery_fee' => 12000,
            'hub_income' => 95000,
            'hub_bonus' => 15000,
            'customer_tips' => 6000,
            'notes' => 'Cek poin jam 21.00 di Suhat',
            'balances' => [
                [
                    'account_id' => $cashAcc->id,
                    'actual_balance' => 55000,
                ],
                [
                    'account_id' => $shopeePay->id,
                    'actual_balance' => 80000,
                ],
            ],
        ];

        $response = $this->post('/operational/digital-reconciliation', $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check DriverSnapshot created
        $this->assertDatabaseHas('driver_snapshots', [
            'work_day_id' => $this->workDay->id,
            'completed_orders' => 10,
            'delivery_fee' => 12000,
            'hub_income' => 95000,
            'hub_bonus' => 15000,
            'customer_tips' => 6000,
            'cash_balance' => 55000,
            'notes' => 'Cek poin jam 21.00 di Suhat',
        ]);

        // Cash balance updated
        $this->assertEquals(55000, $cashAcc->fresh()->current_balance);

        // Dashboard renders checkpoint widget
        $dashResponse = $this->get('/dashboard');
        $dashResponse->assertOk();
        $dashResponse->assertSee('Riwayat Checkpoint Jam Narik & Komparasi Saldo', false);
        $dashResponse->assertSee('Cek poin jam 21.00 di Suhat');
        $dashResponse->assertSee('10 Pesanan Selesai');
    }

    public function test_reconcile_digital_properly_separates_cod_food_bill_from_pure_qris_tip_and_allocates_correctly(): void
    {
        $gopay = Account::where('name', 'GoPay')->firstOrFail();
        $gopay->update(['current_balance' => 10000]);

        // Skenario Nyata Driver:
        // Customer COD pesan makanan Rp 45.000, tapi bayar transfer via QRIS GoPay Rp 50.000 (kasih tip Rp 5.000).
        // Saldo GoPay driver di aplikasi HP bertambah +50.000 menjadi Rp 60.000.
        // Driver memasukkan tagihan pokok COD Rp 45.000 agar tidak ditelan mentah-mentah sebagai tip!
        $payload = [
            'work_day_id' => $this->workDay->id,
            'cod_qris_amount' => 45000,
            'balances' => [
                [
                    'account_id' => $gopay->id,
                    'actual_balance' => 60000,
                ],
            ],
        ];

        $response = $this->post('/operational/digital-reconciliation', $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 1. Saldo GoPay terupdate menjadi 60.000
        $this->assertEquals(60000, $gopay->fresh()->current_balance);

        // 2. Transaksi 1: Modal COD Makanan Rp 45.000 masuk melunasi Talangan Pesanan COD (Bukan Tip!)
        $talanganAccount = Account::where('name', 'Talangan Pesanan COD')->firstOrFail();
        $this->assertDatabaseHas('transactions', [
            'work_day_id' => $this->workDay->id,
            'description' => 'Pelunasan Tagihan COD Customer via QRIS GoPay: Rp 45.000 (Pengembalian Modal Makanan)',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $talanganAccount->id,
            'entry_type' => 'credit',
            'amount' => 45000,
        ]);

        // 3. Transaksi 2: Hanya sisa kelebihan Rp 5.000 yang diakui sebagai Tip QRIS Murni
        $qrisTipAccount = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->firstOrFail();
        $this->assertDatabaseHas('transactions', [
            'work_day_id' => $this->workDay->id,
            'description' => 'Surplus Tip QRIS Murni GoPay: +Rp 5.000',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $qrisTipAccount->id,
            'entry_type' => 'credit',
            'amount' => 5000,
        ]);

        // 4. Pastikan session flash message memuat info alokasi modal COD & tip murni
        $response->assertSessionHas('success', function ($msg) {
            return str_contains($msg, 'Modal COD via QRIS Rp 45.000 dialokasikan ke Talangan')
                && str_contains($msg, 'Tip QRIS Murni +Rp 5.000');
        });
    }
}

