# TASK-065: Fitur Export Laporan Mutasi & Cash Flow CSV & PDF (`/export/...`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Controller Export File Streaming CSV & Laporan PDF Mutasi Buku Besar  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun fitur export data mutasi keuangan dan ringkasan arus kas ke format CSV dan PDF:
1. **Export CSV (`GET /export/transactions/csv`):**
   - Mendukung filter: rentang tanggal (`start_date`, `end_date`), akun (`account_id`), dan kategori (`category`).
   - Format kolom: Tanggal/Waktu (WIB), No Transaksi, Akun Sumber, Akun Tujuan, Jenis Mutasi (Debit/Kredit), Jumlah (Rp), Kategori, Catatan/Keterangan.
   - Menggunakan streaming response (`StreamedResponse`) agar hemat memori saat mengekspor ribuan baris transaksi.
2. **Export Ringkasan Laporan PDF (`GET /export/report/pdf`):**
   - Menghasilkan dokumen cetak ringkas bulanan (Rekap Arus Kas, Pendapatan Bersih Shopee, Biaya Operasional Bensin & Parkir, Pembayaran Cicilan).
   - Layout bersih dan profesional khusus arsip pribadi.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/ExportReportController.php` — Controller penanganan export CSV dan PDF.
2. `resources/views/reports/monthly_pdf.blade.php` — Template blade dokumen cetak laporan.
3. `routes/web.php` — Pendaftaran route `GET /export/transactions/csv` dan `GET /export/report/pdf`.
4. `tests/Feature/Web/ExportReportTest.php` — Automated test endpoint download file CSV dan PDF.

---

## 3. Spesifikasi Anatomi Kode (`app/Http/Controllers/Web/ExportReportController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportReportController extends Controller
{
    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $transactions = Transaction::whereBetween('transaction_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['sourceAccount', 'destinationAccount'])
            ->orderBy('transaction_time', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"mutasi_{$startDate}_{$endDate}.csv\"",
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Waktu', 'No Referensi', 'Akun Sumber', 'Akun Tujuan', 'Jumlah (Rp)', 'Kategori', 'Catatan']);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->transaction_time->format('Y-m-d H:i:s'),
                    $tx->reference_number ?? ('TX-' . $tx->id),
                    $tx->sourceAccount?->name ?? '-',
                    $tx->destinationAccount?->name ?? '-',
                    $tx->amount,
                    $tx->category,
                    $tx->notes ?? '',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `GET /export/transactions/csv` menghasilkan file CSV dengan header `Content-Type: text/csv` dan header nama kolom yang valid.
2. Setiap baris transaksi di-export dengan presisi integer Rupiah tanpa desimal terpotong.
3. Test automated memverifikasi isi baris CSV sesuai dengan data transaksi di database.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/ExportReportTest.php`
- **Metode Uji:**
  1. `test_export_transactions_csv_downloads_valid_csv_file()`:
     - Buat 2 transaksi contoh.
     - Panggil `GET /export/transactions/csv` $\rightarrow$ Pastikan HTTP 200, Header Content-Disposition attachment CSV, dan body response memuat referensi transaksi.
