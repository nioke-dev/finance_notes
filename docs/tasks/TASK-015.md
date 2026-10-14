# TASK-015: Custom Exception Penjaga Keseimbangan Ledger (`UnbalancedLedgerException`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Domain Exception Spesifik untuk Menolak Transaksi Tidak Seimbang  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun custom PHP exception `UnbalancedLedgerException`. Exception ini adalah "penjaga gerbang" integritas akuntansi di backend:
- Dipicu ketika ada percobaan mencatat transaksi keuangan di mana total nominal sisi Debit tidak sama persis dengan total nominal sisi Kredit ($\sum \text{Debit} \ne \sum \text{Kredit}$).
- Menyimpan konteks data matematis kegagalan:
  - `totalDebit`: Total nominal debit yang dihitung.
  - `totalCredit`: Total nominal kredit yang dihitung.
  - `discrepancy`: Selisih nominal ketidakseimbangan ($|\text{Debit} - \text{Credit}|$).
- Membantu debugging dan pelaporan error HTTP `422 Unprocessable Entity` yang sangat informatif bagi frontend.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Exceptions/UnbalancedLedgerException.php` — Custom exception class.
2. `tests/Unit/Exceptions/UnbalancedLedgerExceptionTest.php` — Automated test verifikasi exception.

---

## 3. Spesifikasi Anatomi Kode (`app/Exceptions/UnbalancedLedgerException.php`)
```php
namespace App\Exceptions;

use Exception;

class UnbalancedLedgerException extends Exception
{
    protected int $totalDebit;
    protected int $totalCredit;
    protected int $discrepancy;

    public function __construct(int $totalDebit, int $totalCredit)
    {
        $this->totalDebit = $totalDebit;
        $this->totalCredit = $totalCredit;
        $this->discrepancy = abs($totalDebit - $totalCredit);

        parent::__construct(
            "Transaksi ditolak: Buku ledger tidak seimbang. Total Debit (Rp{$totalDebit}) != Total Kredit (Rp{$totalCredit}). Selisih: Rp{$this->discrepancy}."
        );
    }

    public function getTotalDebit(): int
    {
        return $this->totalDebit;
    }

    public function getTotalCredit(): int
    {
        return $this->totalCredit;
    }

    public function getDiscrepancy(): int
    {
        return $this->discrepancy;
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Class `UnbalancedLedgerException` tersimpan di namespace `App\Exceptions`.
2. Exception menyimpan dan menyediakan getter untuk `totalDebit`, `totalCredit`, dan `discrepancy`.
3. Pesan error menyebutkan nominal debit, kredit, dan selisih secara eksplisit.
4. Unit test lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Exceptions/UnbalancedLedgerExceptionTest.php`
- **Metode Uji:**
  1. `test_exception_calculates_discrepancy_and_message_accurately()`:
     - Melempar exception dengan Debit 100.000 dan Kredit 98.000 $\rightarrow$ `getDiscrepancy()` mengembalikan 2000, pesan memuat "Rp2000".
