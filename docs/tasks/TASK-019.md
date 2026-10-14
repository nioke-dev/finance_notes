# TASK-019: Service Pembalikan Transaksi Tanpa Hard Delete (`ReversalService`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Logika Reversal Transaksi Keuangan & Audit Trail Koreksi  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `ReversalService` yang bertanggung jawab membatalkan atau mengoreksi transaksi keuangan. Sesuai prinsip rekayasa di `AGENTS.md` dan `BUSINESS-RULES.md`:
1. Sistem **DILARANG KERAS** menghapus baris transaksi (`hard delete`) atau mengedit nilai nominal transaksi lama secara diam-diam.
2. Pembatalan dilakukan dengan membuat **Transaksi Pembalik (Reversal Transaction)**:
   - Jika transaksi asal mendebit Akun A Rp10.000, transaksi pembalik akan mengkredit Akun A Rp10.000.
   - Jika transaksi asal mengkredit Akun B Rp10.000, transaksi pembalik akan mendebit Akun B Rp10.000.
   - Saldo berjalan `current_balance` pada masing-masing akun otomatis dinetralkan kembali.
3. Transaksi asal ditandai tipe-nya atau diberi metadata `reversed_by_transaction_id`.
4. Wajib menyertakan parameter `reason` (alasan pembatalan/koreksi) dan otomatis dicatat ke tabel `audit_logs`.
5. Transaksi yang sudah pernah di-reverse **DILARANG** di-reverse untuk kedua kalinya.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/ReversalService.php` — Service pembalikan transaksi.
2. `tests/Feature/Services/ReversalServiceTest.php` — Automated test feature integritas pembalikan saldo dan pencatatan audit.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/ReversalService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReversalService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function reverseTransaction(Transaction $originalTransaction, string $reason): Transaction
    {
        if (strlen(trim($reason)) < 5) {
            throw new InvalidArgumentException("Alasan pembatalan transaksi wajib diisi minimal 5 karakter.");
        }

        if ($originalTransaction->type === 'reversal') {
            throw new InvalidArgumentException("Transaksi pembalik (reversal) tidak dapat dibatalkan kembali.");
        }

        $isAlreadyReversed = Transaction::where('metadata->reversed_transaction_id', $originalTransaction->id)->exists();
        if ($isAlreadyReversed) {
            throw new InvalidArgumentException("Transaksi ini sudah pernah dibatalkan sebelumnya.");
        }

        return DB::transaction(function () use ($originalTransaction, $reason) {
            $reverseEntries = [];

            // Membalik setiap entri debit menjadi kredit, dan kredit menjadi debit
            foreach ($originalTransaction->ledgerEntries as $entry) {
                $invertedType = ($entry->entry_type === EntryType::DEBIT) ? EntryType::CREDIT : EntryType::DEBIT;

                $reverseEntries[] = [
                    'account_id' => $entry->account_id,
                    'entry_type' => $invertedType,
                    'amount' => $entry->amount,
                ];
            }

            // Buat transaksi pembalik via LedgerService
            $reversalTransaction = $this->ledgerService->recordTransaction(
                workDay: $originalTransaction->workDay,
                type: 'reversal',
                description: "Pembalikan Transaksi #{$originalTransaction->id}: {$originalTransaction->description}",
                transactionTime: now(),
                entries: $reverseEntries,
                isEstimatedTime: false,
                metadata: [
                    'reversed_transaction_id' => $originalTransaction->id,
                    'reason' => $reason,
                ]
            );

            // Catat ke audit log
            AuditLog::create([
                'auditable_type' => Transaction::class,
                'auditable_id' => $originalTransaction->id,
                'action' => 'reversal',
                'old_values' => [
                    'transaction_id' => $originalTransaction->id,
                    'type' => $originalTransaction->type,
                    'description' => $originalTransaction->description,
                ],
                'new_values' => [
                    'reversal_transaction_id' => $reversalTransaction->id,
                ],
                'reason' => $reason,
            ]);

            return $reversalTransaction;
        });
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Transaksi pembalik berhasil menetralkan saldo akun-akun terkait kembali persis ke posisi sebelum transaksi asal dibuat.
2. Transaksi asal tidak terhapus dari tabel `transactions`.
3. Tabel `audit_logs` menyimpan rekam jejak pembatalan dengan alasan yang jelas.
4. Mencegah pembatalan ganda pada transaksi yang sama.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/ReversalServiceTest.php`
- **Metode Uji:**
  1. `test_reversal_restores_account_balances_to_exact_previous_state()`:
     - Transaksi Bensin Rp30.000 memotong Kas $\rightarrow$ Reversal mengembalikan saldo Kas bertambah Rp30.000 dan saldo Beban Bensin berkurang Rp30.000.
  2. `test_cannot_reverse_same_transaction_twice()`:
     - Memastikan eksekusi reversal kedua melempar exception.
  3. `test_reversal_requires_reason_and_creates_audit_log()`:
     - Memastikan row baru tercipta di `audit_logs`.
