# TASK-023: Service Filter Jam Kerja Eligible Bonus Hub (Rentang 10.00 – 20.00)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** Aturan Validasi Jam Pesanan Eligible Bonus Hub  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `ShopeeBonusEligibilityService` yang bertanggung jawab memfilter pesanan yang berhak dihitung ke dalam skema tingkatan bonus hub.
Sesuai aturan bisnis resmi di `BUSINESS-RULES.md`:
1. **Jendela Waktu Bonus:** Hanya pesanan yang masuk dalam rentang pukul **10:00:00 hingga 20:00:00** yang eligible dihitung ke dalam kuota tingkatan bonus.
2. Pesanan di luar jendela waktu tersebut (misalnya pukul 08.00 pagi atau 21.00 malam):
   - Tetap sah menghasilkan pendapatan reguler (Pendapatan Hub).
   - **TIDAK BOLEH** dihitung sebagai kuota order untuk menaikkan tier bonus.
3. Service menyediakan method:
   - `isTimeEligible(Carbon $time): bool`
   - `filterEligibleOrders(Collection|array $orders): Collection`

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/ShopeeBonusEligibilityService.php` — Service filter kelayakan jam pesanan.
2. `tests/Unit/Services/ShopeeBonusEligibilityServiceTest.php` — Automated test unit pengujian batas waktu.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/ShopeeBonusEligibilityService.php`)
```php
namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ShopeeBonusEligibilityService
{
    public const WINDOW_START = '10:00:00';
    public const WINDOW_END = '20:00:00';

    public function isTimeEligible(Carbon $time): bool
    {
        $timeString = $time->format('H:i:s');

        return $timeString >= self::WINDOW_START && $timeString <= self::WINDOW_END;
    }

    /**
     * @param Collection|array $orders Array of ['order_number' => string, 'completed_at' => Carbon]
     */
    public function filterEligibleOrders(Collection|array $orders): Collection
    {
        $collection = is_array($orders) ? collect($orders) : $orders;

        return $collection->filter(function ($order) {
            $time = ($order['completed_at'] instanceof Carbon) 
                ? $order['completed_at'] 
                : Carbon::parse($order['completed_at']);

            return $this->isTimeEligible($time);
        });
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Pesanan pada pukul `09:59:59` mengembalikan `false`.
2. Pesanan pada pukul `10:00:00` mengembalikan `true`.
3. Pesanan pada pukul `20:00:00` mengembalikan `true`.
4. Pesanan pada pukul `20:00:01` mengembalikan `false`.
5. Method `filterEligibleOrders` menyaring koleksi order secara akurat.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Services/ShopeeBonusEligibilityServiceTest.php`
- **Metode Uji:**
  1. `test_orders_before_10_am_are_ineligible()`
  2. `test_orders_between_10_am_and_8_pm_are_eligible()`
  3. `test_orders_after_8_pm_are_ineligible()`
  4. `test_filter_eligible_orders_extracts_correct_count_from_mixed_dataset()`:
     - Diberikan 30 pesanan (5 sebelum jam 10, 20 di jam kerja, 5 setelah jam 20) $\rightarrow$ Hasil filter tepat 20 order.
