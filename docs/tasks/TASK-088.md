# TASK-088: Halaman Web Konfigurasi Skema Bonus ShopeeFood (`GET & POST /settings/shopee-bonus`)

**Modul:** 6. Web Super App Dashboard & Visualisasi  
**Fokus Tunggal:** Controller, View Blade, & Form Dinamis Skema Bonus Hub Malang di Web Admin  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun antarmuka web di `/settings/shopee-bonus` agar driver dapat mengubah parameter bonus bertingkat jika ShopeeFood Malang melakukan update skema:
1. **Fitur Pengaturan Dinamis:**
   - Menampilkan tabel tingkatan tiering aktif (Tier 1: 16–20 order @ Rp1.000, Tier 2: 21–25 order @ Rp1.500, Tier 3: 26+ order @ Rp2.000).
   - Form ubah target minimum order dan nilai bonus per order.
   - Form ubah jam cut-off (default: 20.00 WIB) dan batas minimum jam online (default: 9.0 jam).
2. **Audit Trail Perubahan Skema:**
   - Setiap kali skema bonus diubah, riwayat perubahan tersimpan di tabel `audit_logs` agar perhitungan hari-hari lampau tidak terdistorsi.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/ShopeeBonusSettingController.php` — Controller konfigurasi skema bonus.
2. `resources/views/pages/settings/shopee_bonus.blade.php` — Tampilan form setting skema bonus Blade.
3. `routes/web.php` — Pendaftaran route `GET & POST /settings/shopee-bonus`.
4. `tests/Feature/Web/ShopeeBonusSettingTest.php` — Automated test form update skema bonus.

---

## 3. Spesifikasi Anatomi Kode (`ShopeeBonusSettingController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ShopeeBonusScheme;
use Illuminate\Http\Request;

class ShopeeBonusSettingController extends Controller
{
    public function index()
    {
        $schemes = ShopeeBonusScheme::orderBy('tier_level', 'asc')->get();
        return view('pages.settings.shopee_bonus', compact('schemes'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'schemes' => ['required', 'array'],
            'schemes.*.id' => ['required', 'exists:shopee_bonus_schemes,id'],
            'schemes.*.rate_per_trip' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['schemes'] as $item) {
            ShopeeBonusScheme::where('id', $item['id'])->update([
                'rate_per_trip' => $item['rate_per_trip'],
            ]);
        }

        return redirect()->route('settings.shopee-bonus')->with('success', 'Skema bonus ShopeeFood berhasil diperbarui.');
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Route `GET /settings/shopee-bonus` merender form skema bonus aktif dengan status 200.
2. Form update memperbarui nilai bonus di database tanpa error.
3. Automated test memverifikasi rendering dan proses pembaruan skema.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/ShopeeBonusSettingTest.php`
- **Metode Uji:**
  1. `test_shopee_bonus_settings_page_can_be_accessed()`:
     - Akses `GET /settings/shopee-bonus` $\rightarrow$ Pastikan HTTP 200.
  2. `test_driver_can_update_bonus_rates_successfully()`:
     - Submit perubahan `rate_per_trip` $\rightarrow$ Pastikan redirect dan data di database terupdate.
