# TASK-062: Halaman Web Analitik Performa Driver ShopeeFood (`GET /analytics/driver`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Controller, View Blade, & Grafik Tren Performa ShopeeFood  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun halaman web analitik komprehensif khusus performa driver ShopeeFood di rute `/analytics/driver`.
Halaman ini dirancang untuk diakses melalui laptop/PC guna mengevaluasi efektivitas harian dan mingguan saat on-beat di Kota Malang:
1. **Filter Periode:** 7 Hari Terakhir, 30 Hari Terakhir, atau Custom Range Tanggal.
2. **Key Performance Indicators (KPI Cards):**
   - Total Trip / Order Diselesaikan.
   - Total Pendapatan Bersih (Tarif Dasar Hub + Tip QRIS/Tunai + Total Bonus).
   - Rata-rata Pendapatan per Jam Online.
   - Rasio Keberhasilan Pencapaian Bonus Tier (berapa hari tembus target $\ge 16$ order jam 20.00).
3. **Visualisasi Grafik (Chart.js / SVG / HTML Bar Chart):**
   - Tren Harian: Order Selesai vs Target Bonus (garis target 16, 21, 26).
   - Breakdown Pendapatan: Komponen Ongkir Hub vs Tip Langsung vs Bonus Shopee.
4. **Daftar Hari Performa Terbaik & Terlemah:** Membantu mengidentifikasi hari dan jam gacor.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/DriverAnalyticsController.php` — Controller web agregasi metrik performa driver.
2. `resources/views/pages/analytics/driver.blade.php` — Halaman Blade analitik performa driver.
3. `routes/web.php` — Pendaftaran route `GET /analytics/driver`.
4. `tests/Feature/Web/DriverAnalyticsTest.php` — Automated test halaman analitik.

---

## 3. Spesifikasi Anatomi Kode (`app/Http/Controllers/Web/DriverAnalyticsController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WorkDay;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DriverAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->input('days', 7);
        $startDate = Carbon::today()->subDays($days - 1)->toDateString();

        $workDays = WorkDay::where('date', '>=', $startDate)
            ->orderBy('date', 'asc')
            ->get();

        $totalTrips = $workDays->sum('total_trips');
        $totalShopeeIncome = $workDays->sum('shopee_cash_collected'); // dan saldo
        $bonusDaysCount = $workDays->filter(fn($w) => $w->bonus_amount > 0)->count();

        return view('pages.analytics.driver', compact('workDays', 'totalTrips', 'totalShopeeIncome', 'bonusDaysCount', 'days'));
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `GET /analytics/driver` mengembalikan status 200 dengan view `pages.analytics.driver`.
2. Ringkasan total trip, total pendapatan shopee, dan frekuensi tembus bonus teragregasi secara presisi.
3. Mendukung query parameter `?days=7` dan `?days=30`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/DriverAnalyticsTest.php`
- **Metode Uji:**
  1. `test_driver_analytics_page_can_be_accessed_and_displays_kpi_cards()`:
     - Buat beberapa data `WorkDay` dengan trip dan bonus.
     - Akses `GET /analytics/driver` $\rightarrow$ Pastikan status 200 dan angka ringkasan sesuai di HTML.
  2. `test_driver_analytics_supports_period_filtering()`:
     - Akses `GET /analytics/driver?days=30` $\rightarrow$ Response 200 dengan data rentang 30 hari.
