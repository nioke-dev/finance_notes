<?php

namespace Tests\Feature\Web;

use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\Debt;
use App\Models\WorkDay;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashOperationalWebTest extends TestCase
{
    use RefreshDatabase;

    protected Account $cashAccount;
    protected Account $bankAccount;
    protected WorkDay $workDay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DefaultAccountsSeeder::class);

        $this->cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $this->cashAccount->update([
            'current_balance' => 200000,
            'initial_balance' => 200000,
        ]);

        $this->bankAccount = Account::where('name', 'BCA')->firstOrFail();
        $this->bankAccount->update([
            'current_balance' => 500000,
            'initial_balance' => 500000,
        ]);

        $this->workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
        ]);
    }

    public function test_lend_friend_cash_creates_debt_deducts_cash_and_logs_audit(): void
    {
        $response = $this->post('/operational/lend-friend-cash', [
            'work_day_id' => $this->workDay->id,
            'borrower_name' => 'Budi Santoso',
            'amount' => 50000,
            'notes' => 'Pinjam untuk talangan bensin di basecamp',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify cash account balance deducted: 200.000 - 50.000 = 150.000
        $this->assertEquals(150000, $this->cashAccount->fresh()->current_balance);

        // Verify debt record created
        $debt = Debt::where('borrower_name', 'Budi Santoso')->first();
        $this->assertNotNull($debt);
        $this->assertEquals(50000, $debt->principal_amount);
        $this->assertEquals('person', $debt->platform);
        $this->assertEquals('active', $debt->status);
        $this->assertStringContainsString('Budi Santoso', $debt->name);

        // Verify receivable account created
        $receivableAccount = Account::where('name', 'Piutang Teman - Budi Santoso')->first();
        $this->assertNotNull($receivableAccount);
        $this->assertEquals(50000, $receivableAccount->current_balance);
    }

    public function test_lend_friend_cash_requires_borrower_name_and_positive_amount(): void
    {
        $response = $this->post('/operational/lend-friend-cash', [
            'work_day_id' => $this->workDay->id,
            'borrower_name' => '',
            'amount' => 0,
        ]);

        $response->assertSessionHasErrors(['borrower_name', 'amount']);
        $this->assertEquals(200000, $this->cashAccount->fresh()->current_balance);
    }

    public function test_cash_withdraw_shortcut_moves_money_from_bank_to_cash(): void
    {
        $response = $this->post('/operational/transfer', [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $this->bankAccount->id,
            'destination_account_id' => $this->cashAccount->id,
            'amount' => 100000,
            'admin_fee' => 2500,
            'note' => 'Tarik tunai modal receh kembalian COD di Indomaret',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Bank balance: 500.000 - 100.000 - 2.500 = 397.500
        $this->assertEquals(397500, $this->bankAccount->fresh()->current_balance);
        // Cash balance: 200.000 + 100.000 = 300.000
        $this->assertEquals(300000, $this->cashAccount->fresh()->current_balance);
    }

    public function test_save_denominations_stores_damaged_notes_and_provides_recommendations(): void
    {
        $response = $this->post('/closing/' . $this->workDay->id . '/denominations', [
            'bill_100000' => 1,
            'bill_50000' => 1,
            'bill_20000' => 1,
            'damaged_notes_amount' => 20000,
            'damaged_notes_note' => 'Sobek sedikit di tepi, uang kembalian martabak',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $denomination = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        $this->assertNotNull($denomination);
        $this->assertEquals(170000, $denomination->total_physical_amount);
        $this->assertEquals(20000, $denomination->damaged_notes_amount);
        $this->assertEquals('Sobek sedikit di tepi, uang kembalian martabak', $denomination->damaged_notes_note);

        // Check recommendations
        $recommendations = $denomination->getRecommendations();
        $this->assertNotEmpty($recommendations);
        $hasDamagedAlert = false;
        foreach ($recommendations as $rec) {
            if ($rec['field'] === 'damaged_notes') {
                $hasDamagedAlert = true;
                $this->assertStringContainsString('Rp 20.000', $rec['message']);
            }
        }
        $this->assertTrue($hasDamagedAlert);

        // Test rendering on closing page
        $viewResponse = $this->get('/closing/' . $this->workDay->id);
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Perhatian Lembaran Uang Rusak / Sobek');
        $viewResponse->assertSee('Sobek sedikit di tepi, uang kembalian martabak');
    }

    public function test_user_can_record_parking_dynamically_and_when_offbid(): void
    {
        // 1. Record motorbike parking with Rp 1.000
        $response = $this->post('/operational/parking', [
            'work_day_id' => $this->workDay->id,
            'amount' => 1000,
            'restaurant_name' => 'Indomaret Point',
            'vehicle_type' => 'motor',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(199000, $this->cashAccount->fresh()->current_balance);

        // 2. Record car parking when off-bid (no work_day_id provided)
        $response2 = $this->post('/operational/parking', [
            'amount' => 5000,
            'restaurant_name' => 'Mall Olympic Garden',
            'vehicle_type' => 'mobil',
            'source_account_id' => $this->bankAccount->id,
        ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('success');
        $this->assertEquals(495000, $this->bankAccount->fresh()->current_balance);
    }

    public function test_user_can_record_parking_with_gps_and_view_on_dashboard(): void
    {
        $response = $this->post('/operational/parking', [
            'work_day_id' => $this->workDay->id,
            'amount' => 2000,
            'restaurant_name' => 'KFC Suhat',
            'order_code' => 'SP-9988',
            'latitude' => -7.9421234,
            'longitude' => 112.6178912,
            'vehicle_type' => 'motor',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tx = \App\Models\Transaction::where('work_day_id', $this->workDay->id)
            ->where('description', 'like', '%KFC Suhat #SP-9988%')
            ->firstOrFail();

        $this->assertEquals(-7.9421234, $tx->metadata['latitude']);
        $this->assertEquals(112.6178912, $tx->metadata['longitude']);

        // Check on dashboard
        $user = \App\Models\User::factory()->create();
        $dashResponse = $this->actingAs($user)->get('/dashboard');
        $dashResponse->assertOk();
        $dashResponse->assertSee('https://www.google.com/maps?q=-7.9421234,112.6178912', false);
        $dashResponse->assertSee('Maps');
    }

    public function test_personal_expense_with_cash_breakdown_updates_cash_denominations(): void
    {
        // Seed baseline denomination for the shift
        $denom = CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_50000' => 4, // Rp 200.000
            'total_physical_amount' => 200000,
        ]);

        // Makan siang Rp 15.000, bayar pakai selembar 50rb, kembalian 35rb (20k + 10k + 5k)
        $response = $this->post('/operational/personal-expense', [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $this->cashAccount->id,
            'amount' => 15000,
            'description' => 'Makan Siang Soto Ayam Lamongan',
            'paid_breakdown' => [
                'bill_50000' => 1,
            ],
            'change_breakdown' => [
                'bill_20000' => 1,
                'bill_10000' => 1,
                'bill_5000' => 1,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Cash account reduced by 15.000
        $this->assertEquals(185000, $this->cashAccount->fresh()->current_balance);

        // Denominations updated: 50k: 4 - 1 = 3 (150k), 20k: 1 (20k), 10k: 1 (10k), 5k: 1 (5k) -> Total = 185k
        $updatedDenom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        $this->assertEquals(3, $updatedDenom->bill_50000);
        $this->assertEquals(1, $updatedDenom->bill_20000);
        $this->assertEquals(1, $updatedDenom->bill_10000);
        $this->assertEquals(1, $updatedDenom->bill_5000);
        $this->assertEquals(185000, $updatedDenom->total_physical_amount);

        // Transaction metadata records cash breakdown
        $tx = \App\Models\Transaction::where('work_day_id', $this->workDay->id)
            ->where('description', 'Makan Siang Soto Ayam Lamongan')
            ->firstOrFail();
        $this->assertNotNull($tx->metadata['cash_breakdown'] ?? null);
        $this->assertEquals(1, $tx->metadata['cash_breakdown']['paid']['bill_50000']);
        $this->assertEquals(1, $tx->metadata['cash_breakdown']['change']['bill_20000']);
    }

    public function test_field_incidental_with_cash_breakdown_updates_cash_denominations(): void
    {
        $denom = CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_50000' => 4, // 200.000
            'total_physical_amount' => 200000,
        ]);

        // Tambal ban Rp 15.000, bayar uang 50k, kembalian 35k (20k + 10k + 5k)
        $response = $this->post('/operational/field-incidental-expense', [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $this->cashAccount->id,
            'amount' => 15000,
            'description' => 'Tambal ban tubeless belakang',
            'paid_breakdown' => [
                'bill_50000' => 1,
            ],
            'change_breakdown' => [
                'bill_20000' => 1,
                'bill_10000' => 1,
                'bill_5000' => 1,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(185000, $this->cashAccount->fresh()->current_balance);

        $updatedDenom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        $this->assertEquals(3, $updatedDenom->bill_50000);
        $this->assertEquals(1, $updatedDenom->bill_20000);
        $this->assertEquals(185000, $updatedDenom->total_physical_amount);

        $tx = \App\Models\Transaction::where('work_day_id', $this->workDay->id)
            ->where('description', 'like', '%Tambal ban tubeless belakang%')
            ->firstOrFail();
        $this->assertNotNull($tx->metadata['cash_breakdown'] ?? null);
    }

    public function test_lend_friend_with_cash_breakdown_updates_cash_denominations(): void
    {
        $denom = CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_50000' => 4, // 200.000
            'total_physical_amount' => 200000,
        ]);

        // Pinjamkan Rp 50.000 (1 lembar 50k)
        $response = $this->post('/operational/lend-friend-cash', [
            'work_day_id' => $this->workDay->id,
            'borrower_name' => 'Agus Driver',
            'amount' => 50000,
            'notes' => 'Pinjam buat bensin',
            'paid_breakdown' => [
                'bill_50000' => 1,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(150000, $this->cashAccount->fresh()->current_balance);

        $updatedDenom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        $this->assertEquals(3, $updatedDenom->bill_50000);
        $this->assertEquals(150000, $updatedDenom->total_physical_amount);

        $tx = \App\Models\Transaction::where('work_day_id', $this->workDay->id)
            ->where('description', 'like', '%Agus Driver%')
            ->firstOrFail();
        $this->assertNotNull($tx->metadata['cash_breakdown'] ?? null);
        $this->assertEquals(1, $tx->metadata['cash_breakdown']['outflow']['bill_50000']);
    }

    public function test_cash_withdraw_with_cash_breakdown_increments_cash_denominations(): void
    {
        $denom = CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_50000' => 2, // 100.000
            'total_physical_amount' => 100000,
        ]);

        // Tarik tunai Rp 100.000 dari BCA ke Cash: dapat selembar 100k
        $response = $this->post('/operational/transfer', [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $this->bankAccount->id,
            'destination_account_id' => $this->cashAccount->id,
            'amount' => 100000,
            'admin_fee' => 0,
            'note' => 'Tarik tunai ATM BCA',
            'paid_breakdown' => [
                'bill_100000' => 1,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(300000, $this->cashAccount->fresh()->current_balance);

        $updatedDenom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        $this->assertEquals(1, $updatedDenom->bill_100000);
        $this->assertEquals(2, $updatedDenom->bill_50000);
        $this->assertEquals(200000, $updatedDenom->total_physical_amount);

        $tx = \App\Models\Transaction::where('work_day_id', $this->workDay->id)
            ->where('description', 'like', '%Tarik tunai ATM BCA%')
            ->firstOrFail();
        $this->assertNotNull($tx->metadata['cash_breakdown'] ?? null);
        $this->assertEquals(1, $tx->metadata['cash_breakdown']['inflow']['bill_100000']);
    }

    public function test_personal_income_with_cash_breakdown_increments_cash_denominations(): void
    {
        $denom = CashDenomination::create([
            'work_day_id' => $this->workDay->id,
            'bill_50000' => 2, // 100.000
            'total_physical_amount' => 100000,
        ]);

        // Pemasukan freelance Rp 50.000 diterima cash
        $response = $this->post('/operational/personal-income', [
            'work_day_id' => $this->workDay->id,
            'destination_account_id' => $this->cashAccount->id,
            'amount' => 50000,
            'description' => 'Freelance Desain Logo',
            'paid_breakdown' => [
                'bill_50000' => 1,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(250000, $this->cashAccount->fresh()->current_balance);

        $updatedDenom = CashDenomination::where('work_day_id', $this->workDay->id)->first();
        $this->assertEquals(3, $updatedDenom->bill_50000);
        $this->assertEquals(150000, $updatedDenom->total_physical_amount);

        $tx = \App\Models\Transaction::where('work_day_id', $this->workDay->id)
            ->where('description', 'Freelance Desain Logo')
            ->firstOrFail();
        $this->assertNotNull($tx->metadata['cash_breakdown'] ?? null);
        $this->assertEquals(1, $tx->metadata['cash_breakdown']['inflow']['bill_50000']);
    }
}

