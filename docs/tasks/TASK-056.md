# TASK-056: Service Resolusi Selisih Hari Lampau (`DiscrepancyResolutionService`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Penambahan Transaksi Terlupa ke Hari Lampau dengan Placeholder Waktu 23:59  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `DiscrepancyResolutionService` yang menangani skenario investigasi driver ketika berhasil mengingat transaksi yang terlupa di hari lampau.
Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
1. **Skenario Lapangan Nyata:**
   - Hari kemarin (12 Oktober) ada selisih minus Rp15.000.
   - Siang hari ini (13 Oktober) driver teringat: *"Oh iya, semalam beli kopi dan rokok di warung Rp15.000 lupa dicatat"*.
   - Driver mengklik banner pengingat dan menambahkan transaksi pengeluaran Rp15.000 ke hari kerja 12 Oktober tersebut.
2. **Aturan Placeholder Waktu & Audit Trail:**
   - Jika driver tidak ingat jam aslinya, sistem mengizinkan penggunaan placeholder pukul `23:59:00` pada tanggal kerja terkait.
   - Kolom `is_estimated_time` otomatis diset `true` (menandai bukan jam faktual).
   - Wajib menyertakan alasan input terlambat (`late_entry_reason`) yang otomatis dicatat ke tabel `audit_logs`.
3. Setelah transaksi tersimpan via `LedgerService`, saldo dihitung ulang detik itu juga $\rightarrow$ jika selisih menjadi Rp0, sistem mengizinkan finalisasi.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/DiscrepancyResolutionService.php` — Service resolusi selisih.
2. `tests/Feature/Services/DiscrepancyResolutionServiceTest.php` — Automated test feature resolusi transaksi terlupa.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/DiscrepancyResolutionService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DiscrepancyResolutionService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function addForgottenExpense(
        WorkDay $pastWorkDay,
        Account $sourceAccount,
        Account $expenseAccount,
        int $amount,
        string $description,
        string $reason,
        bool $usePlaceholderTime = true,
        ?Carbon $actualTime = null
    ): Transaction {
        if ($pastWorkDay->isFinal()) {
            throw new InvalidArgumentException("Hari kerja ini sudah final, tidak dapat menambahkan transaksi secara langsung.");
        }

        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan penambahan transaksi terlupa wajib diisi minimal 5 karakter.");
        }

        $transactionTime = $usePlaceholderTime 
            ? $pastWorkDay->work_date->copy()->setTime(23, 59, 0) 
            : ($actualTime ?? now());

        $transaction = $this->ledgerService->recordTransaction(
            workDay: $pastWorkDay,
            type: 'expense',
            description: "{$description} (Input Terlambat)",
            transactionTime: $transactionTime,
            entries: [
                ['account_id' => $sourceAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $amount],
                ['account_id' => $expenseAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $amount],
            ],
            isEstimatedTime: $usePlaceholderTime,
            metadata: [
                'type' => 'late_entry_resolution',
                'reason' => $reason,
                'resolved_at' => now()->toISOString(),
            ]
        );

        AuditLog::create([
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'action' => 'create',
            'old_values' => null,
            'new_values' => ['amount' => $amount, 'description' => $description],
            'reason' => "Input transaksi terlupa ke hari lampau: {$reason}",
        ]);

        return $transaction;
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Penambahan pengeluaran terlupa Rp15.000 ke hari lampau berhasil memotong saldo buku kas.
2. Kolom `is_estimated_time` bernilai `true` dan jam transaksi tercatat `23:59:00`.
3. Record baru tercatat di tabel `audit_logs` dengan alasan input terlambat.
4. Mencegah penambahan langsung ke hari kerja yang sudah berstatus `final`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/DiscrepancyResolutionServiceTest.php`
- **Metode Uji:**
  1. `test_adding_forgotten_expense_to_past_work_day_sets_placeholder_and_logs_audit()`:
     - Input terlambat Rp15.000 $\rightarrow$ jam transaksi 23:59:00, is_estimated_time true, audit log tercatat.
  2. `test_cannot_add_transaction_to_already_finalized_work_day()`:
     - Hari status final $\rightarrow$ melempar InvalidArgumentException.
