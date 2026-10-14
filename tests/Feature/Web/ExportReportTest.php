<?php

namespace Tests\Feature\Web;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_transactions_csv_downloads_valid_csv_file(): void
    {
        $workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $account = Account::create([
            'name' => 'BCA',
            'type' => 'bank',
            'current_balance' => 500000,
            'is_active' => true,
        ]);

        $tx = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'income',
            'description' => 'Pendapatan Bersih ShopeeFood',
            'transaction_time' => now(),
            'is_estimated_time' => false,
        ]);

        LedgerEntry::create([
            'transaction_id' => $tx->id,
            'account_id' => $account->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => 150000,
            'created_at' => now(),
        ]);

        $response = $this->get('/export/transactions/csv');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));

        // Ambil output stream
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Pendapatan Bersih ShopeeFood', $content);
        $this->assertStringContainsString('150000', $content);
        $this->assertStringContainsString('TX-' . str_pad($tx->id, 6, '0', STR_PAD_LEFT), $content);
    }

    public function test_export_report_pdf_view_renders_monthly_summary(): void
    {
        $workDay = WorkDay::create([
            'work_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $account = Account::create([
            'name' => 'Kas Tunai Dompet',
            'type' => 'cash',
            'current_balance' => 200000,
            'is_active' => true,
        ]);

        $tx = Transaction::create([
            'work_day_id' => $workDay->id,
            'type' => 'expense',
            'description' => 'Bensin Pertalite SPBU',
            'transaction_time' => now(),
            'is_estimated_time' => false,
        ]);

        LedgerEntry::create([
            'transaction_id' => $tx->id,
            'account_id' => $account->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => 25000,
            'created_at' => now(),
        ]);

        $response = $this->get('/export/report/pdf');

        $response->assertStatus(200);
        $response->assertViewIs('reports.monthly_pdf');
        $response->assertSee('Laporan Rekapitulasi Arus Kas Bulanan');
        $response->assertSee('Bensin Pertalite SPBU');
        $response->assertSee('25.000');
    }
}
