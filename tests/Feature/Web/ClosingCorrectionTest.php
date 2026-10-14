<?php

namespace Tests\Feature\Web;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\VoiceMemo;
use App\Models\WorkDay;
use App\Services\LedgerService;
use Database\Seeders\DefaultAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosingCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected Account $cashAccount;
    protected Account $bankAccount;
    protected WorkDay $workDay;
    protected LedgerService $ledgerService;

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

        $this->ledgerService = app(LedgerService::class);
    }

    public function test_closing_page_renders_voice_memos_and_cash_transactions(): void
    {
        // 1. Create a voice memo for today with work_day_id
        VoiceMemo::create([
            'work_day_id' => $this->workDay->id,
            'file_path' => 'voice-memos/test.mp3',
            'latitude' => -7.9839,
            'longitude' => 112.6214,
            'recorded_at' => now(),
            'status' => 'pending',
        ]);

        // 2. Create a cash transaction (Beli bensin Rp30.000)
        $fuelExpense = Account::where('name', 'Beban Bensin Kendaraan')->firstOrFail();
        $this->ledgerService->recordTransaction(
            workDay: $this->workDay,
            type: 'expense',
            description: 'Beli Pertalite SPBU',
            transactionTime: now(),
            entries: [
                ['account_id' => $this->cashAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => 30000],
                ['account_id' => $fuelExpense->id, 'entry_type' => EntryType::DEBIT, 'amount' => 30000],
            ]
        );

        $response = $this->get('/closing/' . $this->workDay->id);

        $response->assertStatus(200);
        $response->assertSee('Kotak Hitam Helm (Voice Memos)');
        $response->assertSee('Mutasi Kas Shift Ini');
        $response->assertSee('Beli Pertalite SPBU');
        $response->assertSee('Koreksi');
        $response->assertSee('modal-correct-closing-tx');
    }

    public function test_in_place_transaction_correction_reverses_old_tx_and_creates_new_tx(): void
    {
        $fuelExpense = Account::where('name', 'Beban Bensin Kendaraan')->firstOrFail();
        
        // Initial balance: 200.000. Spent 30.000 -> cash balance becomes 170.000
        $tx = $this->ledgerService->recordTransaction(
            workDay: $this->workDay,
            type: 'expense',
            description: 'Beli Bensin Salah Nominal',
            transactionTime: now()->subHours(2),
            entries: [
                ['account_id' => $this->cashAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => 30000],
                ['account_id' => $fuelExpense->id, 'entry_type' => EntryType::DEBIT, 'amount' => 30000],
            ]
        );

        $this->assertEquals(170000, $this->cashAccount->fresh()->current_balance);

        // Koreksi nominal dari Rp30.000 menjadi Rp20.000 (ternyata beli Rp20.000)
        $response = $this->post("/closing/{$this->workDay->id}/transactions/{$tx->id}/correct", [
            'new_amount' => 20000,
            'reason' => 'Salah catat, struk bensin aslinya Rp20.000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check reversal transaction was created
        $reversalTx = Transaction::where('type', 'reversal')->first();
        $this->assertNotNull($reversalTx);
        $this->assertEquals($tx->id, $reversalTx->metadata['reversed_transaction_id']);

        // Check new corrected transaction exists
        $newTx = Transaction::where('metadata->corrected_from_transaction_id', $tx->id)->first();
        $this->assertNotNull($newTx);
        $this->assertEquals(20000, $newTx->amount);

        // Cash balance should be: 200.000 - 20.000 = 180.000
        $this->assertEquals(180000, $this->cashAccount->fresh()->current_balance);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Transaction::class,
            'auditable_id' => $tx->id,
            'action' => 'in_place_closing_correction',
        ]);
    }

    public function test_field_incidental_expense_deducts_cash_and_creates_ledger_entry(): void
    {
        // Initial cash: 200.000
        $response = $this->post('/operational/field-incidental-expense', [
            'work_day_id' => $this->workDay->id,
            'description' => 'Tambal ban bocor di Sawojajar',
            'amount' => 15000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify cash balance deducted: 200.000 - 15.000 = 185.000
        $this->assertEquals(185000, $this->cashAccount->fresh()->current_balance);

        // Verify incidental expense account debited
        $expenseAcc = Account::where('name', 'Beban Serba-serbi & Insidental Lapangan')->firstOrFail();
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $expenseAcc->id,
            'entry_type' => EntryType::DEBIT->value,
            'amount' => 15000,
        ]);

        // Verify transaction was created
        $this->assertDatabaseHas('transactions', [
            'work_day_id' => $this->workDay->id,
            'description' => 'Biaya Serba-serbi: Tambal ban bocor di Sawojajar',
        ]);
    }

    public function test_field_incidental_expense_can_use_digital_account(): void
    {
        // Initial bank: 500.000
        $response = $this->post('/operational/field-incidental-expense', [
            'work_day_id' => $this->workDay->id,
            'source_account_id' => $this->bankAccount->id,
            'description' => 'Beli lakban packing via QRIS',
            'amount' => 12000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Cash remains 200.000, BCA becomes 488.000
        $this->assertEquals(200000, $this->cashAccount->fresh()->current_balance);
        $this->assertEquals(488000, $this->bankAccount->fresh()->current_balance);
    }
}
