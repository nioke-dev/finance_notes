# TASK-052: Service Penguncian Finalisasi Closing (`ClosingDayService`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Logika Penguncian Status Final Hari Kerja (Strict Blocking Selisih != 0)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `ClosingDayService` yang bertindak sebagai otoritas penutupan hari kerja driver.
Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
1. Pengguna sangat menolak "adjustment palsu" yang menambal saldo tanpa konteks riil.
2. Hari kerja **DILARANG KERAS** difinalkan (`status = 'final'`) jika masih terdapat selisih uang bahkan Rp1 ($\sum |\text{Discrepancy}| > 0$).
3. **Perilaku Service:**
   - Menjalankan audit rekonsiliasi via `ReconciliationEngineService`.
   - Jika `is_balanced == false`: Melempar `UnresolvedDiscrepancyException` (yang nanti di-handle HTTP 422). Hari kerja tetap berstatus `needs_reconciliation`.
   - Jika `is_balanced == true`: Mengubah status `work_days.status` menjadi `'final'`, mencatat `closed_at = now()`, dan mengunci tanggal tersebut dari penambahan transaksi baru.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Exceptions/UnresolvedDiscrepancyException.php` — Custom exception penolakan closing saat selisih belum selesai.
2. `app/Services/ClosingDayService.php` — Service penutupan hari kerja.
3. `tests/Feature/Services/ClosingDayServiceTest.php` — Automated test feature strict finalization lock.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Exceptions/UnresolvedDiscrepancyException.php`
```php
namespace App\Exceptions;

use Exception;

class UnresolvedDiscrepancyException extends Exception
{
    protected array $discrepantAccounts;

    public function __construct(array $discrepantAccounts)
    {
        $this->discrepantAccounts = $discrepantAccounts;
        parent::__construct("Penutupan hari kerja gagal: Masih terdapat selisih saldo yang belum teridentifikasi.");
    }

    public function getDiscrepantAccounts(): array
    {
        return $this->discrepantAccounts;
    }
}
```

### B. `app/Services/ClosingDayService.php`
```php
namespace App\Services;

use App\Exceptions\UnresolvedDiscrepancyException;
use App\Models\WorkDay;
use InvalidArgumentException;

class ClosingDayService
{
    protected ReconciliationEngineService $reconciliationService;

    public function __construct(ReconciliationEngineService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
    }

    public function finalizeWorkDay(WorkDay $workDay, array $digitalBalances, ?string $closingNotes = null): WorkDay
    {
        if ($workDay->isFinal()) {
            throw new InvalidArgumentException("Hari kerja ini sudah pernah difinalisasi sebelumnya.");
        }

        $report = $this->reconciliationService->reconcileWorkDay($workDay, $digitalBalances);

        if (!$report['is_balanced']) {
            $unmatched = array_filter($report['accounts'], fn ($a) => $a['discrepancy'] !== 0);
            throw new UnresolvedDiscrepancyException(array_values($unmatched));
        }

        $workDay->update([
            'status' => WorkDay::STATUS_FINAL,
            'closed_at' => now(),
            'closing_notes' => $closingNotes,
        ]);

        return $workDay->fresh();
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Hari kerja dengan selisih saldo $\ne 0$ gagal difinalkan dan melempar `UnresolvedDiscrepancyException`.
2. Hari kerja dengan selisih saldo $= 0$ berhasil berubah menjadi `status = 'final'` dan `closed_at` terisi.
3. Hari kerja yang sudah `final` tidak dapat difinalkan kembali.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/ClosingDayServiceTest.php`
- **Metode Uji:**
  1. `test_cannot_finalize_work_day_if_discrepancy_exists()`:
     - Ada selisih Rp500 $\rightarrow$ melempar UnresolvedDiscrepancyException, status tetap `needs_reconciliation`.
  2. `test_successfully_finalizes_work_day_when_discrepancy_is_zero()`:
     - Selisih Rp0 $\rightarrow$ status berubah `final`, closed_at tercatat.
