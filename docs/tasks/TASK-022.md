# TASK-022: Service Perhitungan Bonus Hub Bertingkat (`ShopeeBonusCalculatorService`)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** Logika Matematika Perhitungan Bonus Hub Murni Bertingkat Progresif  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `ShopeeBonusCalculatorService` yang mengimplementasikan perhitungan bonus hub driver ShopeeFood di Malang.
Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
1. **Perhitungan Murni Bertingkat (Incremental Progressive)**:
   - Jika driver dapat 26 order:
     - Order 1–15: Rp0
     - Order 16–20 (5 order): $5 \times \text{Rp}1.000 = \text{Rp}5.000$
     - Order 21–25 (5 order): $5 \times \text{Rp}1.500 = \text{Rp}7.500$
     - Order 26 (1 order): $1 \times \text{Rp}2.000 = \text{Rp}2.000$
     - Total Bonus = $\text{Rp}5.000 + \text{Rp}7.500 + \text{Rp}2.000 = \text{Rp}14.500$.
   - Jika driver dapat 31 order:
     - $5.000 + 7.500 + (6 \times 2.000) = \text{Rp}24.500$.
2. Membaca skema tingkatan langsung dari tabel `shopee_bonus_schemes` (atau fallback konfigurasi jika database kosong).
3. Selalu mengembalikan integer Rupiah utuh.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/ShopeeBonusCalculatorService.php` — Service kalkulator bonus hub.
2. `tests/Unit/Services/ShopeeBonusCalculatorServiceTest.php` — Automated test unit pengujian matematis seluruh skenario order.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/ShopeeBonusCalculatorService.php`)
```php
namespace App\Services;

use App\Models\ShopeeBonusScheme;

class ShopeeBonusCalculatorService
{
    public function calculateBonus(int $totalEligibleOrders): int
    {
        if ($totalEligibleOrders <= 15) {
            return 0;
        }

        $schemes = ShopeeBonusScheme::active()->orderBy('tier_level')->get();

        // Fallback default jika tabel belum terisi data seeder
        if ($schemes->isEmpty()) {
            return $this->calculateWithDefaultRules($totalEligibleOrders);
        }

        $totalBonus = 0;

        foreach ($schemes as $scheme) {
            if ($totalEligibleOrders < $scheme->min_orders) {
                continue;
            }

            $maxEligibleInThisTier = $scheme->max_orders 
                ? min($totalEligibleOrders, $scheme->max_orders) 
                : $totalEligibleOrders;

            $ordersInThisTier = ($maxEligibleInThisTier - $scheme->min_orders) + 1;

            if ($ordersInThisTier > 0) {
                $totalBonus += $ordersInThisTier * $scheme->rate_per_order;
            }
        }

        return $totalBonus;
    }

    protected function calculateWithDefaultRules(int $orders): int
    {
        $bonus = 0;

        // Tier 2 (16 - 20): Rp1.000
        if ($orders >= 16) {
            $count = min($orders, 20) - 15;
            $bonus += $count * 1000;
        }

        // Tier 3 (21 - 25): Rp1.500
        if ($orders >= 21) {
            $count = min($orders, 25) - 20;
            $bonus += $count * 1500;
        }

        // Tier 4 (26+): Rp2.000
        if ($orders >= 26) {
            $count = $orders - 25;
            $bonus += $count * 2000;
        }

        return $bonus;
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perhitungan 0 s/d 15 order menghasilkan bonus Rp0.
2. Perhitungan 18 order menghasilkan tepat Rp3.000.
3. Perhitungan 26 order menghasilkan tepat Rp14.500.
4. Perhitungan 31 order menghasilkan tepat Rp24.500.
5. Unit tests lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Services/ShopeeBonusCalculatorServiceTest.php`
- **Metode Uji:**
  1. `test_orders_below_or_equal_to_15_returns_zero_bonus()`
  2. `test_orders_in_tier_two_calculates_correctly()`
  3. `test_sample_case_26_orders_returns_14500_rupiah_exact()`
  4. `test_sample_case_31_orders_returns_24500_rupiah_exact()`
