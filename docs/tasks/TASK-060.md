# TASK-060: Halaman Web Closing Harian & Audit Denominasi Fisik (`GET /closing/{id}`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Antarmuka Closing Malam, Kalkulator Fisik Denominasi, & Perbandingan Selisih  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Controller dan View Blade untuk halaman closing harian dan rekonsiliasi kas di laptop (`/closing/{id}`).
Sesuai rancangan di `PRD.md` dan `UI-DESIGN-SYSTEM.md`:
1. **Sisi Kiri (Form Hitung Fisik):**
   - Grid input kuantitas lembar uang kertas (Rp1.000 s/d Rp100.000) dan koin (Rp100 s/d Rp1.000).
   - Menghitung total fisik secara realtime via JavaScript sederhana tanpa reload.
2. **Sisi Kanan (Audit Saldo & Perbandingan):**
   - Menampilkan tabel saldo buku ledger vs saldo aktual fisik dan digital.
   - Menampilkan selisih uang per akun dengan badge warna visual (`<x-status-badge>`).
   - Tombol **"Finalkan Hari Kerja"**: Otomatis dinonaktifkan (*disabled*) jika selisih saldo $\ne$ Rp0.
   - Tombol *"Simpan Draft / Tutup Nanti"*: Selalu aktif agar driver bisa tidur tanpa terkunci jika belum menemukan transaksi terlupa.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/ClosingWebController.php` — Controller web closing.
2. `resources/views/closing/show.blade.php` — View Blade halaman closing.
3. `routes/web.php` — Mendaftarkan route web `GET /closing/{id}`.
4. `tests/Feature/Web/ClosingWebPageTest.php` — Automated test HTTP request web closing.

---

## 3. Spesifikasi Anatomi Kode (`ClosingWebController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;
use App\Services\ReconciliationEngineService;
use Illuminate\View\View;

class ClosingWebController extends Controller
{
    public function show(int $id, ReconciliationEngineService $reconciliationService): View
    {
        $workDay = WorkDay::with('voiceMemos')->findOrFail($id);
        $accounts = Account::active()->get();
        $denominations = CashDenomination::firstOrNew(['work_day_id' => $workDay->id]);
        $reconciliationReport = $reconciliationService->reconcileWorkDay($workDay, []);

        return view('closing.show', compact(
            'workDay',
            'accounts',
            'denominations',
            'reconciliationReport'
        ));
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Route `GET /closing/{id}` merespons HTTP `200 OK`.
2. Halaman menampilkan grid form input pecahan uang tunai.
3. Tombol finalisasi dinonaktifkan secara visual jika ada selisih uang.
4. Menampilkan daftar Voice Memo yang direkam pada hari tersebut (jika ada).

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/ClosingWebPageTest.php`
- **Metode Uji:**
  1. `test_closing_web_page_loads_successfully_with_http_200()`
  2. `test_closing_page_renders_denomination_inputs()`
  3. `test_finalize_button_disabled_when_discrepancy_exists()`
