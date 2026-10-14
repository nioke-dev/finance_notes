# TASK-064: Halaman Web Pengelolaan Utang & Jadwal Cicilan (`GET /debts`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Controller, View Blade, & Form Interaktif Pelunasan Utang / Piutang  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun halaman web pengelolaan portofolio pinjaman dan piutang di `/debts`.
Halaman ini menyediakan tampilan lengkap untuk memantau liabilitas dan piutang pribadi:
1. **Ringkasan Liabilitas (Total Outstanding Debts):**
   - Total Kewajiban Utang Belum Lunas (PayLater, Pinjaman Bank/Keluarga).
   - Total Piutang ke Teman yang Belum Kembali.
   - Peringatan Jatuh Tempo: Daftar tagihan dalam 7 hari & 30 hari ke depan dengan status badge mencolok.
2. **Tab Navigasi:**
   - Tab 1: **Utang Saya (PayLater / Pinjaman)** — Daftar cicilan, pokok vs bunga/biaya, dan tombol bayar cicilan.
   - Tab 2: **Piutang Teman** — Daftar teman yang meminjam uang, tanggal pinjam, sisa saldo, tombol pelunasan cepat dengan opsi auto-sedekah jika dibayar kurang.
3. **Modal Form Input:**
   - Form Tambah Pinjaman Baru (dengan input biaya admin/provisi yang otomatis memotong saldo pencairan bersih sesuai `TASK-046`).
   - Form Pelunasan Cicilan SPayLater dengan input promo potongan diskon 0% bunga sesuai `TASK-047`.
   - Form Pelunasan Utang Teman dengan checkbox `"Ikhlaskan sisa sebagai sedekah"` sesuai `TASK-048`.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/DebtWebController.php` — Controller web pengelolaan data utang & piutang.
2. `resources/views/pages/debts/index.blade.php` — Halaman Blade tabel utang, tab piutang teman, & form modal.
3. `routes/web.php` — Pendaftaran route `GET /debts`.
4. `tests/Feature/Web/DebtWebControllerTest.php` — Automated test halaman web utang.

---

## 3. Spesifikasi Anatomi Kode (`app/Http/Controllers/Web/DebtWebController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\Account;
use Illuminate\Http\Request;

class DebtWebController extends Controller
{
    public function index(Request $request)
    {
        $myDebts = Debt::where('type', 'payable')
            ->where('status', 'active')
            ->with('installments')
            ->orderBy('due_date', 'asc')
            ->get();

        $friendDebts = Debt::where('type', 'receivable')
            ->where('status', 'active')
            ->orderBy('due_date', 'asc')
            ->get();

        $totalPayable = $myDebts->sum('remaining_amount');
        $totalReceivable = $friendDebts->sum('remaining_amount');

        $paymentAccounts = Account::where('is_active', true)->get();

        return view('pages.debts.index', compact(
            'myDebts',
            'friendDebts',
            'totalPayable',
            'totalReceivable',
            'paymentAccounts'
        ));
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Route `GET /debts` mengembalikan status 200 dan merender view `pages.debts.index`.
2. Menampilkan pemisahan utang kewajiban (payable) dan piutang teman (receivable).
3. Total remaining amount terhitung tepat tanpa desimal pembulatan.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/DebtWebControllerTest.php`
- **Metode Uji:**
  1. `test_debts_index_displays_active_debts_and_summary_totals()`:
     - Buat data Debt tipe payable Rp500.000 dan receivable Rp100.000.
     - Akses `GET /debts` $\rightarrow$ Pastikan status 200, kedua data tampil, dan total agregasi sesuai di view.
