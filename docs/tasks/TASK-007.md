# TASK-007: Service Logika Saldo Awal & Audit Trail Koreksi (`InitialBalanceService`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Service Mutasi Saldo Awal (Bukan Pendapatan) & Audit Logging  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `InitialBalanceService` yang menangani logika domain keuangan terkait saldo awal akun.
Sesuai aturan baku di `BUSINESS-RULES.md`:
1. Saldo awal adalah **modal awal**, bukan pendapatan. Saldo awal **DILARANG** membuat mutasi di akun `income` atau mempengaruhi laporan laba/rugi.
2. Inisialisasi saldo awal pertama kali langsung menetapkan `initial_balance` dan `current_balance` pada tabel `accounts`.
3. Jika di kemudian hari saldo awal diedit (karena salah hitung saat awal setup):
   - Wajib menyertakan alasan koreksi (`reason`).
   - Selisih perubahan disesuaikan pada `current_balance`.
   - Wajib otomatis membuat row baru di tabel `audit_logs` (menyimpan nilai lama, nilai baru, timestamp, dan alasan).

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/InitialBalanceService.php` — Class service domain saldo awal.
2. `tests/Unit/Services/InitialBalanceServiceTest.php` — Automated test logika saldo awal dan audit log.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/InitialBalanceService.php`)
```php
namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InitialBalanceService
{
    public function setInitialBalance(Account $account, int $amount): Account
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Saldo awal tidak boleh negatif.");
        }

        return DB::transaction(function () use ($account, $amount) {
            $account->update([
                'initial_balance' => $amount,
                'current_balance' => $amount,
            ]);

            AuditLog::create([
                'auditable_type' => Account::class,
                'auditable_id' => $account->id,
                'action' => 'create',
                'old_values' => null,
                'new_values' => ['initial_balance' => $amount],
                'reason' => 'Inisialisasi saldo awal akun pertama kali.',
            ]);

            return $account->fresh();
        });
    }

    public function correctInitialBalance(Account $account, int $newAmount, string $reason): Account
    {
        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan koreksi saldo awal wajib diisi minimal 5 karakter.");
        }

        return DB::transaction(function () use ($account, $newAmount, $reason) {
            $oldInitial = $account->initial_balance;
            $difference = $newAmount - $oldInitial;

            $account->update([
                'initial_balance' => $newAmount,
                'current_balance' => $account->current_balance + $difference,
            ]);

            AuditLog::create([
                'auditable_type' => Account::class,
                'auditable_id' => $account->id,
                'action' => 'update',
                'old_values' => ['initial_balance' => $oldInitial],
                'new_values' => ['initial_balance' => $newAmount],
                'reason' => $reason,
            ]);

            return $account->fresh();
        });
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Inisialisasi saldo awal berhasil menetapkan `initial_balance` dan `current_balance` secara atomik dalam database transaction.
2. Koreksi saldo awal menghitung selisih dan menyesuaikan `current_balance` secara presisi.
3. Koreksi saldo awal tanpa alasan yang valid melempar `InvalidArgumentException`.
4. Tabel `audit_logs` merekam setiap peristiwa perubahan saldo awal.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Services/InitialBalanceServiceTest.php`
- **Metode Uji:**
  1. `test_set_initial_balance_successfully_sets_initial_and_current_balance()`:
     - Set saldo BCA Rp1.000.000 $\rightarrow$ `initial_balance` = 1000000, `current_balance` = 1000000.
  2. `test_correct_initial_balance_adjusts_current_balance_by_exact_difference()`:
     - Saldo awal awal Rp1.000.000, dikoreksi jadi Rp1.200.000 $\rightarrow$ `current_balance` naik Rp200.000.
  3. `test_correct_initial_balance_requires_valid_reason_and_logs_audit()`:
     - Koreksi dengan alasan valid menciptakan audit log dengan snapshot nilai lama dan baru.
