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

    public function test_checkpoint_does_not_require_cash_in_balances_and_auto_tracks_system_cash(): void
    {
        $cashAcc = Account::where('name', 'Cash')->firstOrFail();
        $cashAcc->update(['current_balance' => 77500]);

        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $gopay = Account::where('name', 'GoPay')->firstOrFail();
        $gopayMerchant = Account::where('name', 'GoPay Merchant')->firstOrFail();

        $shopeePay->update(['current_balance' => 50000]);
        $gopay->update(['current_balance' => 20000]);
        $gopayMerchant->update(['current_balance' => 15000]);

        // Input checkpoint HANYA 3 digital wallet (tanpa Cash)
        $payload = [
            'work_day_id' => $this->workDay->id,
            'completed_orders' => 8,
            'delivery_fee' => 75000,
            'customer_tips' => 5000,
            'hub_bonus' => 10000,
            'hub_income' => 0,
            'balances' => [
                ['account_id' => $shopeePay->id, 'actual_balance' => 50000],
                ['account_id' => $gopay->id, 'actual_balance' => 20000],
                ['account_id' => $gopayMerchant->id, 'actual_balance' => 15000],
            ],
        ];

        $response = $this->post('/operational/digital-reconciliation', $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Cash dompet otomatis tercatat Rp77.500 dari sistem buku besar kas tanpa diinput manual
        $this->assertDatabaseHas('driver_snapshots', [
            'work_day_id' => $this->workDay->id,
            'completed_orders' => 8,
            'delivery_fee' => 75000,
            'cash_balance' => 77500,
            'shopeepay_balance' => 50000,
            'gopay_balance' => 20000,
            'gopay_merchant_balance' => 15000,
        ]);
    }

    public function test_cannot_save_checkpoint_with_time_less_than_or_equal_to_previous_checkpoint(): void
    {
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $shopeePay->update(['current_balance' => 50000]);

        // Checkpoint 1 jam 14:00:00
        \App\Models\DriverSnapshot::create([
            'work_day_id' => $this->workDay->id,
            'recorded_at' => now()->startOfDay()->addHours(14),
            'completed_orders' => 5,
            'delivery_fee' => 40000,
            'hub_income' => 0,
            'hub_bonus' => 0,
            'customer_tips' => 0,
            'shopeepay_balance' => 50000,
            'gopay_balance' => 0,
            'gopay_merchant_balance' => 0,
            'cash_balance' => 10000,
        ]);

        // Coba input Checkpoint 2 dengan waktu yang MUNDUR (13:59:59)
        $payloadBackwards = [
            'work_day_id' => $this->workDay->id,
            'is_custom_time' => 1,
            'custom_recorded_at' => now()->startOfDay()->addHours(13)->addMinutes(59)->addSeconds(59)->format('Y-m-d H:i:s'),
            'completed_orders' => 6,
            'delivery_fee' => 50000,
            'balances' => [
                ['account_id' => $shopeePay->id, 'actual_balance' => 50000],
            ],
        ];

        $responseBackwards = $this->post('/operational/digital-reconciliation', $payloadBackwards);
        $responseBackwards->assertRedirect();
        $responseBackwards->assertSessionHas('error');
        $this->assertTrue(str_contains(session('error'), 'tidak boleh mendahului atau sama dengan checkpoint sebelumnya'));

        // Coba input Checkpoint 2 dengan waktu yang SAMA PERSIS (14:00:00)
        $payloadEqual = [
            'work_day_id' => $this->workDay->id,
            'is_custom_time' => 1,
            'custom_recorded_at' => now()->startOfDay()->addHours(14)->format('Y-m-d H:i:s'),
            'completed_orders' => 6,
            'delivery_fee' => 50000,
            'balances' => [
                ['account_id' => $shopeePay->id, 'actual_balance' => 50000],
            ],
        ];

        $responseEqual = $this->post('/operational/digital-reconciliation', $payloadEqual);
        $responseEqual->assertRedirect();
        $responseEqual->assertSessionHas('error');
        $this->assertTrue(str_contains(session('error'), 'tidak boleh mendahului atau sama dengan checkpoint sebelumnya'));
    }

    public function test_can_save_checkpoint_with_custom_time_strictly_greater_than_previous_checkpoint(): void
    {
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $shopeePay->update(['current_balance' => 50000]);

        // Checkpoint 1 jam 14:00:00
        \App\Models\DriverSnapshot::create([
            'work_day_id' => $this->workDay->id,
            'recorded_at' => now()->startOfDay()->addHours(14),
            'completed_orders' => 5,
            'delivery_fee' => 40000,
            'hub_income' => 0,
            'hub_bonus' => 0,
            'customer_tips' => 0,
            'shopeepay_balance' => 50000,
            'gopay_balance' => 0,
            'gopay_merchant_balance' => 0,
            'cash_balance' => 10000,
        ]);

        // Checkpoint 2 jam 14:00:01 (Beda 1 detik lebih baru)
        $payloadValid = [
            'work_day_id' => $this->workDay->id,
            'is_custom_time' => 1,
            'custom_recorded_at' => now()->startOfDay()->addHours(14)->addSecond()->format('Y-m-d H:i:s'),
            'completed_orders' => 6,
            'delivery_fee' => 50000,
            'balances' => [
                ['account_id' => $shopeePay->id, 'actual_balance' => 50000],
            ],
        ];

        $responseValid = $this->post('/operational/digital-reconciliation', $payloadValid);
        $responseValid->assertRedirect();
        $responseValid->assertSessionHas('success');

        $this->assertDatabaseHas('driver_snapshots', [
            'work_day_id' => $this->workDay->id,
            'completed_orders' => 6,
            'delivery_fee' => 50000,
        ]);
    }

    public function test_cannot_save_checkpoint_with_time_in_the_future(): void
    {
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $shopeePay->update(['current_balance' => 50000]);

        $payloadFuture = [
            'work_day_id' => $this->workDay->id,
            'is_custom_time' => 1,
            'custom_recorded_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'completed_orders' => 5,
            'delivery_fee' => 50000,
            'balances' => [
                ['account_id' => $shopeePay->id, 'actual_balance' => 50000],
            ],
        ];

        $responseFuture = $this->post('/operational/digital-reconciliation', $payloadFuture);
        $responseFuture->assertRedirect();
        $responseFuture->assertSessionHas('error');
        $this->assertTrue(str_contains(session('error'), 'tidak boleh melebihi waktu sekarang (masa depan)'));
    }

    public function test_can_record_direct_quick_tip_via_cash_and_qris(): void
    {
        $cashAcc = Account::where('name', 'Cash')->firstOrFail();
        $gopayMerchant = Account::where('name', 'GoPay Merchant')->firstOrFail();

        // 1. Tip Cash Rp 5.000
        $responseCash = $this->post('/operational/quick-tip', [
            'work_day_id' => $this->workDay->id,
            'amount' => 5000,
            'account_id' => $cashAcc->id,
            'notes' => 'Customer ramah antar geprek Suhat',
        ]);
        $responseCash->assertRedirect();
        $responseCash->assertSessionHas('success');

        $tipCashAccount = Account::where('name', 'Tip Cash / Surplus COD')->firstOrFail();
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $tipCashAccount->id,
            'entry_type' => 'credit',
            'amount' => 5000,
        ]);
        $this->assertDatabaseHas('transactions', [
            'work_day_id' => $this->workDay->id,
            'description' => 'Tip Langsung Pelanggan via Cash: Rp 5.000 (Customer ramah antar geprek Suhat)',
        ]);

        // 2. Tip QRIS via GoPay Merchant Rp 10.000
        $responseQris = $this->post('/operational/quick-tip', [
            'work_day_id' => $this->workDay->id,
            'amount' => 10000,
            'account_id' => $gopayMerchant->id,
            'notes' => 'Tip via QRIS meja 4',
        ]);
        $responseQris->assertRedirect();
        $responseQris->assertSessionHas('success', function ($msg) {
            return str_contains($msg, 'GoPay Merchant otomatis dicairkan ke GoPay reguler setiap 1 jam jika saldo ≥ Rp10.000');
        });

        $qrisTipAccount = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->firstOrFail();
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $qrisTipAccount->id,
            'entry_type' => 'credit',
            'amount' => 10000,
        ]);
    }

    public function test_dashboard_daily_financial_summary_mode_driver_vs_overall_and_parking_evaluation(): void
    {
        $cashAcc = Account::where('name', 'Cash')->firstOrFail();

        // 1. Pengeluaran Bensin (15.000) & Parkir (4.000)
        $fuelResponse = $this->post('/operational/fuel', [
            'work_day_id' => $this->workDay->id,
            'amount' => 15000,
            'source_account_id' => $cashAcc->id,
            'vendor_type' => 'spbu',
        ]);
        $fuelResponse->assertRedirect();
        $fuelResponse->assertSessionHas('success');

        $parkResponse = $this->post('/operational/parking', [
            'work_day_id' => $this->workDay->id,
            'amount' => 4000,
            'source_account_id' => $cashAcc->id,
            'vehicle_type' => 'motor',
        ]);
        $parkResponse->assertRedirect();
        $parkResponse->assertSessionHas('success');

        // 2. Pengeluaran Kebutuhan Pribadi / Non-operasional (25.000)
        $personalExpAccount = Account::firstOrCreate(
            ['name' => 'Biaya Hidup / Jajan'],
            ['type' => AccountType::EXPENSE, 'current_balance' => 0, 'initial_balance' => 0, 'is_active' => true]
        );
        $ledgerService = app(\App\Services\LedgerService::class);
        $ledgerService->recordTransaction(
            workDay: $this->workDay,
            type: 'expense',
            description: 'Makan siang & es teh',
            transactionTime: now(),
            entries: [
                ['account_id' => $cashAcc->id, 'entry_type' => \App\Enums\EntryType::CREDIT, 'amount' => 25000],
                ['account_id' => $personalExpAccount->id, 'entry_type' => \App\Enums\EntryType::DEBIT, 'amount' => 25000],
            ],
            isEstimatedTime: false,
            metadata: ['type' => 'personal_lifestyle_expense']
        );

        // 3. Tip Langsung (5.000)
        $this->post('/operational/quick-tip', [
            'work_day_id' => $this->workDay->id,
            'amount' => 5000,
            'account_id' => $cashAcc->id,
            'notes' => 'Tip tunai',
        ]);

        // 4. Driver Snapshot dari ShopeeFood Driver App
        \App\Models\DriverSnapshot::create([
            'work_day_id' => $this->workDay->id,
            'recorded_at' => now(),
            'completed_orders' => 8,
            'delivery_fee' => 60000,
            'hub_income' => 0,
            'hub_bonus' => 0,
            'customer_tips' => 2000, // Tip aplikasi Shopee
            'shopeepay_balance' => 50000,
            'gopay_balance' => 0,
            'gopay_merchant_balance' => 0,
            'cash_balance' => 10000,
        ]);

        // 5. Akses Dashboard
        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertViewHas('dailySummaryData');

        $dailySummary = $response->viewData('dailySummaryData');

        // Mode Driver: Outcome HANYA Bensin (15.000) + Parkir (4.000) = 19.000. Dilarang masuk 25.000 jajan!
        $this->assertEquals(19000, $dailySummary['mode_driver']['outcome']);
        $this->assertEquals(15000, $dailySummary['mode_driver']['breakdown_outcome']['fuel']);
        $this->assertEquals(4000, $dailySummary['mode_driver']['breakdown_outcome']['parking']);

        // Tip Presisi Driver: 2.000 (Shopee app) + 5.000 (Direct Cash) = 7.000
        $this->assertEquals(7000, $dailySummary['mode_driver']['breakdown_income']['total_tips']);

        // Pendapatan Driver: 60.000 (Delivery fee) + 7.000 (Total tip) = 67.000
        $this->assertEquals(67000, $dailySummary['mode_driver']['income']);
        $this->assertEquals(67000 - 19000, $dailySummary['mode_driver']['net']);

        // Evaluasi Parkir: Total Tip (7.000) - Parkir (4.000) = +3.000 (TIDAK NOMBOK / COVERED)
        $this->assertTrue($dailySummary['mode_driver']['parking_eval']['is_covered']);
        $this->assertEquals(3000, $dailySummary['mode_driver']['parking_eval']['net']);

        // Mode Overall: Outcome = Driver (19.000) + Personal (25.000) = 44.000
        $this->assertEquals(44000, $dailySummary['mode_overall']['outcome']);
        $this->assertEquals(25000, $dailySummary['mode_overall']['breakdown_outcome']['personal_expense']);
        $this->assertEquals(67000 - 44000, $dailySummary['mode_overall']['net']);
    }

    public function test_driver_can_update_hub_bonus_and_reflects_in_latest_checkpoint(): void
    {
        // 1. Buat snapshot awal checkpoint
        $snapshot = \App\Models\DriverSnapshot::create([
            'work_day_id' => $this->workDay->id,
            'recorded_at' => now()->startOfDay()->addHours(18),
            'completed_orders' => 12,
            'delivery_fee' => 96000,
            'hub_income' => 0,
            'hub_bonus' => 0,
            'customer_tips' => 0,
            'shopeepay_balance' => 50000,
            'gopay_balance' => 0,
            'gopay_merchant_balance' => 0,
            'cash_balance' => 10000,
        ]);

        // 2. Update bonus hub jam 20:00+ dengan 23 pesanan & sudah online >= 9 jam
        // Formula 23 pesanan: 1-15 (0) + 16-20 (5 * 1000 = 5000) + 21-23 (3 * 1500 = 4500) = 9.500
        $response = $this->post('/operational/update-hub-bonus', [
            'work_day_id' => $this->workDay->id,
            'completed_orders' => 23,
            'is_online_9_hours' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifikasi WorkDay ter-update
        $this->workDay->refresh();
        $this->assertEquals(23, $this->workDay->total_orders);
        $this->assertTrue($this->workDay->is_performance_eligible);
        $this->assertGreaterThanOrEqual(9.0, (float) $this->workDay->online_hours);

        // Verifikasi Checkpoint DriverSnapshot terbaru ter-update nilai bonusnya
        $snapshot->refresh();
        $this->assertEquals(9500, $snapshot->hub_bonus);
        $this->assertEquals(23, $snapshot->completed_orders);
    }

    public function test_driver_cannot_get_hub_bonus_if_online_less_than_9_hours(): void
    {
        $snapshot = \App\Models\DriverSnapshot::create([
            'work_day_id' => $this->workDay->id,
            'recorded_at' => now(),
            'completed_orders' => 25,
            'delivery_fee' => 200000,
            'hub_income' => 0,
            'hub_bonus' => 12500,
            'customer_tips' => 0,
            'shopeepay_balance' => 50000,
            'gopay_balance' => 0,
            'gopay_merchant_balance' => 0,
            'cash_balance' => 10000,
        ]);

        // Kirim tanpa centang is_online_9_hours (online < 9 jam)
        $response = $this->post('/operational/update-hub-bonus', [
            'work_day_id' => $this->workDay->id,
            'completed_orders' => 25,
            'is_online_9_hours' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->workDay->refresh();
        $this->assertFalse($this->workDay->is_performance_eligible);

        // Nilai bonus di checkpoint disetel ke 0 karena < 9 jam
        $snapshot->refresh();
        $this->assertEquals(0, $snapshot->hub_bonus);
    }

    public function test_driver_can_claim_hub_bonus_directly_into_shopeepay_and_prevents_double_claim(): void
    {
        $shopeePay = Account::where('name', 'ShopeePay')->firstOrFail();
        $initialShopeePayBalance = 100000;
        $shopeePay->update(['current_balance' => $initialShopeePayBalance]);

        // Buat snapshot dengan bonus hub 18.500 (28 pesanan)
        $snapshot = \App\Models\DriverSnapshot::create([
            'work_day_id' => $this->workDay->id,
            'recorded_at' => now(),
            'completed_orders' => 28,
            'delivery_fee' => 220000,
            'hub_income' => 0,
            'hub_bonus' => 18500,
            'customer_tips' => 0,
            'shopeepay_balance' => $initialShopeePayBalance,
            'gopay_balance' => 0,
            'gopay_merchant_balance' => 0,
            'cash_balance' => 10000,
        ]);
        $this->workDay->update([
            'total_orders' => 28,
            'is_performance_eligible' => true,
            'online_hours' => 9.5,
        ]);

        // 1. Eksekusi klaim bonus masuk ke ShopeePay
        $response = $this->post('/operational/claim-hub-bonus', [
            'work_day_id' => $this->workDay->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Saldo ShopeePay bertambah Rp 18.500
        $this->assertEquals($initialShopeePayBalance + 18500, $shopeePay->fresh()->current_balance);

        // Transaksi tercatat di buku besar
        $bonusIncomeAcc = Account::where('name', 'Bonus Hub ShopeeFood')->firstOrFail();
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $bonusIncomeAcc->id,
            'entry_type' => 'credit',
            'amount' => 18500,
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $shopeePay->id,
            'entry_type' => 'debit',
            'amount' => 18500,
        ]);
        $this->assertDatabaseHas('transactions', [
            'work_day_id' => $this->workDay->id,
            'description' => 'Pencairan Resmi Bonus Insentif Hub ke ShopeePay: Rp 18.500',
        ]);

        // 2. Proteksi Double-Claim (Mencegah pencairan ganda)
        $responseDuplicate = $this->post('/operational/claim-hub-bonus', [
            'work_day_id' => $this->workDay->id,
        ]);
        $responseDuplicate->assertRedirect();
        $responseDuplicate->assertSessionHas('error');
        $this->assertTrue(str_contains(session('error'), 'sudah pernah dikonfirmasi'));

        // Saldo ShopeePay tetap tidak bertambah lagi
        $this->assertEquals($initialShopeePayBalance + 18500, $shopeePay->fresh()->current_balance);
    }
}


