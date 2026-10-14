# TASK-059: Halaman Web Dashboard Ringkasan Semua Akun & Total Kekayaan (`GET /dashboard`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Halaman Web Utama Ringkasan Seluruh Aset Keuangan & Banner Pengingat  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Controller dan View Blade untuk halaman utama Web Super App (`/dashboard`).
Sesuai rancangan di `PRD.md` dan `BUSINESS-RULES.md`:
1. **Pemanfaatan Layar Luas Laptop:**
   - Menampilkan ringkasan seluruh kekayaan (*Net Worth*) dalam kartu besar di bagian atas: $\text{Total Digital} + \text{Total Cash} - \text{Total Utang Aktif}$.
   - Grid Kartu Akun: Menampilkan kartu saldo individual untuk BCA, SeaBank, ShopeePay, GoPay, GoPay Merchant, dan Cash.
2. **Banner Pengingat Rekonsiliasi Otomatis:**
   - Jika ada hari kerja lampau yang masih berstatus `needs_reconciliation`, kartu peringatan mencolok (Amber/Rose) langsung muncul di atas grid akun, menyertakan link cepat untuk menuju halaman closing tanggal terkait.
3. Tampilan responsif (grid 1 kolom di HP, 3 kolom di laptop/desktop).

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/DashboardController.php` — Controller halaman web dashboard.
2. `resources/views/dashboard/index.blade.php` — View Blade halaman dashboard.
3. `routes/web.php` — Mendaftarkan route web `GET /dashboard`.
4. `tests/Feature/Web/DashboardPageTest.php` — Automated test HTTP request web dashboard.

---

## 3. Spesifikasi Anatomi Kode

### A. Controller Action (`DashboardController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use App\Services\ReconciliationEngineService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(ReconciliationEngineService $reconciliationService): View
    {
        $accounts = Account::active()->get();
        $totalDigital = $accounts->whereIn('type', ['bank', 'ewallet'])->sum('current_balance');
        $totalCash = $accounts->where('type', 'cash')->sum('current_balance');
        $totalDebt = Debt::active()->sum('total_obligation');
        $netWorth = ($totalDigital + $totalCash) - $totalDebt;

        $unfinalizedDays = WorkDay::unfinalized()
            ->where('work_date', '<', now()->format('Y-m-d'))
            ->orderBy('work_date', 'asc')
            ->get();

        return view('dashboard.index', compact(
            'accounts',
            'totalDigital',
            'totalCash',
            'totalDebt',
            'netWorth',
            'unfinalizedDays'
        ));
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Route `GET /dashboard` merespons HTTP `200 OK`.
2. Halaman menampilkan kartu total kekayaan bersih (*net worth*).
3. Halaman menampilkan saldo terkini setiap akun aktif.
4. Banner pengingat muncul jika terdapat hari lampau yang belum final.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/DashboardPageTest.php`
- **Metode Uji:**
  1. `test_dashboard_page_loads_successfully_with_http_200()`
  2. `test_dashboard_displays_all_active_accounts_and_correct_net_worth()`
  3. `test_dashboard_renders_unfinalized_banner_when_pending_days_exist()`
