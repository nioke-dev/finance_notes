# TASK-054: Service Pembukaan Hari Kerja Baru (`OpenWorkDayService`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Logika Izin Membuka Hari Kerja Baru Saat Hari Lama Masih Gantung (Anti-Deadlock)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `OpenWorkDayService` yang menangani pembukaan hari kerja baru bagi driver.
Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
1. Driver sering kelelahan saat malam closing dan belum menemukan penyebab selisih uang pada tanggal kemarin (misal 12 Oktober).
2. Sistem **DILARANG KERAS MEMBLOKIR** driver untuk membuka hari kerja baru keesokan harinya (13 Oktober).
3. **Aturan Service:**
   - Driver bebas memanggil fungsi pembukaan hari kerja baru untuk tanggal kalender hari ini.
   - Sistem mengizinkan pembukaan hari kerja baru meskipun query mendeteksi adanya hari kerja lampau yang masih berstatus `needs_reconciliation`.
   - Saldo awal hari baru otomatis melanjutkan saldo berjalan terkini dari ledger (`current_balance`).
   - Mencegah pembukaan ganda pada tanggal kalender kerja yang sama (`work_date` unik).

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/OpenWorkDayService.php` — Service pembukaan hari kerja baru.
2. `tests/Feature/Services/OpenWorkDayServiceTest.php` — Automated test feature anti-deadlock pembukaan hari kerja baru.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/OpenWorkDayService.php`)
```php
namespace App\Services;

use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class OpenWorkDayService
{
    public function openWorkDay(?Carbon $workDate = null): WorkDay
    {
        $date = ($workDate ?? now())->format('Y-m-d');

        $existingDay = WorkDay::where('work_date', $date)->first();
        if ($existingDay) {
            return $existingDay; // Idempotent: jika sudah buka hari ini, kembalikan data yang ada
        }

        return WorkDay::create([
            'work_date' => $date,
            'status' => WorkDay::STATUS_NEEDS_RECONCILIATION,
            'opened_at' => now(),
            'closed_at' => null,
            'total_orders' => 0,
            'online_hours' => 0.0,
            'is_performance_eligible' => false,
        ]);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Pembukaan hari kerja baru berhasil dibuat meskipun hari kerja kemarin berstatus `needs_reconciliation`.
2. Tanggal kerja baru berstatus `needs_reconciliation` secara default.
3. Membuka hari kerja untuk tanggal yang sudah ada bersifat *idempotent* (mengembalikan record yang sudah aktif tanpa membuat duplikat).

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/OpenWorkDayServiceTest.php`
- **Metode Uji:**
  1. `test_can_open_new_work_day_when_previous_day_is_unfinalized()`:
     - Membuat hari kemarin berstatus `needs_reconciliation` $\rightarrow$ membuka hari baru hari ini sukses 100%.
  2. `test_open_work_day_is_idempotent_for_same_date()`:
     - Membuka hari dua kali pada tanggal yang sama mengembalikan ID yang identik.
