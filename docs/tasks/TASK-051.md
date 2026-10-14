# TASK-051: Service Rekonsiliasi Saldo Buku vs Aktual (`ReconciliationEngineService`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Deteksi Selisih Saldo Buku vs Aktual per Akun Presisi Rp1  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `ReconciliationEngineService` yang menjadi pilar akurasi finansial sistem.
Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
1. Membandingkan dua sumber kebenaran:
   - **Saldo Buku (Ledger Balance):** Nilai `current_balance` pada tabel `accounts` yang dihitung dari seluruh mutasi tercatat.
   - **Saldo Aktual (Actual Real Balance):**
     - Akun `Cash`: Diambil dari `total_physical_amount` tabel `cash_denominations`.
     - Akun Digital (`BCA`, `SeaBank`, `ShopeePay`, `GoPay`, `GoPay Merchant`): Diinput dari saldo rekening riil malam itu.
2. **Rumus Selisih Matematis:**
   $$\text{Selisih (Discrepancy)} = \text{Saldo Aktual} - \text{Saldo Buku}$$
3. Dilarang ada pembulatan atau toleransi desimal (akurasi mutlak Rp1).
4. Menghasilkan laporan terstruktur per akun:
   - Status: `matched` ($\text{selisih} = 0$), `shortage` ($\text{selisih} < 0$), atau `surplus` ($\text{selisih} > 0$).
   - Total selisih bersih seluruh akun ($\sum |\text{Discrepancy}|$).

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/ReconciliationEngineService.php` — Core service rekonsiliasi.
2. `tests/Feature/Services/ReconciliationEngineServiceTest.php` — Automated test feature deteksi selisih buku vs fisik/digital.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/ReconciliationEngineService.php`)
```php
namespace App\Services;

use App\Models\Account;
use App\Models\CashDenomination;
use App\Models\WorkDay;

class ReconciliationEngineService
{
    /**
     * @param array $digitalActualBalances Array of ['account_id' => int, 'actual_balance' => int]
     */
    public function reconcileWorkDay(WorkDay $workDay, array $digitalActualBalances): array
    {
        $report = [
            'work_day_id' => $workDay->id,
            'is_balanced' => true,
            'total_absolute_discrepancy' => 0,
            'accounts' => [],
        ];

        // 1. Rekonsiliasi Akun Cash (Uang Fisik)
        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $physicalRecord = CashDenomination::where('work_day_id', $workDay->id)->first();
        $physicalCashActual = $physicalRecord ? $physicalRecord->total_physical_amount : 0;

        $cashDiscrepancy = $physicalCashActual - $cashAccount->current_balance;

        $report['accounts'][] = [
            'account_id' => $cashAccount->id,
            'account_name' => 'Cash',
            'type' => 'physical',
            'ledger_balance' => $cashAccount->current_balance,
            'actual_balance' => $physicalCashActual,
            'discrepancy' => $cashDiscrepancy,
            'status' => $this->resolveStatus($cashDiscrepancy),
        ];

        if ($cashDiscrepancy !== 0) {
            $report['is_balanced'] = false;
            $report['total_absolute_discrepancy'] += abs($cashDiscrepancy);
        }

        // 2. Rekonsiliasi Akun Digital
        $actualMap = collect($digitalActualBalances)->pluck('actual_balance', 'account_id');
        $digitalAccounts = Account::digital()->active()->get();

        foreach ($digitalAccounts as $account) {
            $actual = $actualMap->get($account->id, $account->current_balance);
            $discrepancy = $actual - $account->current_balance;

            $report['accounts'][] = [
                'account_id' => $account->id,
                'account_name' => $account->name,
                'type' => 'digital',
                'ledger_balance' => $account->current_balance,
                'actual_balance' => $actual,
                'discrepancy' => $discrepancy,
                'status' => $this->resolveStatus($discrepancy),
            ];

            if ($discrepancy !== 0) {
                $report['is_balanced'] = false;
                $report['total_absolute_discrepancy'] += abs($discrepancy);
            }
        }

        return $report;
    }

    protected function resolveStatus(int $discrepancy): string
    {
        if ($discrepancy === 0) {
            return 'matched';
        }

        return $discrepancy > 0 ? 'surplus' : 'shortage';
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Saldo buku Cash Rp350.000 vs Fisik Rp335.000 menghasilkan selisih `-15000` dengan status `shortage`.
2. Saldo buku BCA Rp1.900.000 vs Aktual Rp2.000.000 menghasilkan selisih `+100000` dengan status `surplus`.
3. Jika seluruh akun cocok 100%, `is_balanced` bernilai `true` dan `total_absolute_discrepancy = 0`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/ReconciliationEngineServiceTest.php`
- **Metode Uji:**
  1. `test_reconciliation_detects_exact_cash_shortage()`:
     - Menguji deteksi selisih fisik minus Rp15.000.
  2. `test_reconciliation_detects_digital_surplus()`:
     - Menguji deteksi selisih digital plus Rp100.000.
  3. `test_reconciliation_reports_balanced_when_all_accounts_match_zero()`:
     - Menguji kondisi seimbang 100%.
