# TASK-063: Halaman Web Analitik Pengeluaran Bensin & Kendaraan (`GET /analytics/fuel`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Controller, View Blade, & Analitik Komparasi Efisiensi Bensin (SPBU vs Madura)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun halaman web analitik efisiensi bahan bakar dan operasional motor di `/analytics/fuel`.
Sesuai aturan bisnis di `BUSINESS-RULES.md`:
1. **Analisis Komparasi SPBU Resmi vs Pertamini Madura:**
   - Menghitung total rupiah yang dikeluarkan di SPBU Pertamina (Rp10.000/L) vs Eceran Madura (Rp12.000/botol).
   - Menghitung total volume liter riil yang didapatkan vs potensi selisih "markup harga eceran".
2. **Efisiensi Finansial Biaya per Trip (Cost per Order):**
   - Menghitung rasio biaya bensin terhadap total pendapatan ShopeeFood (misal: 15% dari omzet harian).
   - Menghitung estimasi rata-rata rupiah bensin per order selesai.
3. **Peringatan & Rekomendasi Finansial:**
   - Menampilkan notifikasi insight jika frekuensi beli bensin eceran Madura terlalu sering (> 40% pembelian), menyarankan pengisian penuh di SPBU resmi sebelum jam on-beat.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/FuelAnalyticsController.php` — Controller kalkulasi agregasi pengeluaran bensin.
2. `resources/views/pages/analytics/fuel.blade.php` — Halaman visualisasi efisiensi bensin.
3. `routes/web.php` — Pendaftaran route `GET /analytics/fuel`.
4. `tests/Feature/Web/FuelAnalyticsTest.php` — Automated test halaman analitik bensin.

---

## 3. Spesifikasi Anatomi Kode (`app/Http/Controllers/Web/FuelAnalyticsController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class FuelAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $fuelTransactions = Transaction::where('category', 'operational')
            ->where('notes', 'LIKE', '%Bensin%')
            ->orderBy('transaction_time', 'desc')
            ->get();

        $totalSpbuSpent = 0;
        $totalMaduraSpent = 0;
        $totalSpbuLiters = 0.0;
        $totalMaduraBottles = 0;

        foreach ($fuelTransactions as $tx) {
            if (str_contains($tx->notes, 'SPBU')) {
                $totalSpbuSpent += $tx->amount;
                $totalSpbuLiters += round($tx->amount / 10000, 2);
            } elseif (str_contains($tx->notes, 'Madura')) {
                $totalMaduraSpent += $tx->amount;
                $totalMaduraBottles += (int) ($tx->amount / 12000);
            }
        }

        $totalSpent = $totalSpbuSpent + $totalMaduraSpent;

        return view('pages.analytics.fuel', compact(
            'fuelTransactions',
            'totalSpbuSpent',
            'totalMaduraSpent',
            'totalSpbuLiters',
            'totalMaduraBottles',
            'totalSpent'
        ));
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Route `GET /analytics/fuel` mengembalikan status 200 dengan view `pages.analytics.fuel`.
2. Menghitung pengeluaran SPBU vs Madura dan total volume/liter secara tepat.
3. Menampilkan rasio perbandingan SPBU vs Madura dalam tampilan card analitik dark mode.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/FuelAnalyticsTest.php`
- **Metode Uji:**
  1. `test_fuel_analytics_calculates_spbu_and_madura_breakdown_correctly()`:
     - Buat transaksi bensin SPBU Rp20.000 dan eceran Madura Rp24.000.
     - Akses `GET /analytics/fuel` $\rightarrow$ Pastikan status 200, SPBU Rp20.000, Madura Rp24.000 tertera di response HTML.
