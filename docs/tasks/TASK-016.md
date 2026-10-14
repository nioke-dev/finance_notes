# TASK-016: Service Engine Transaksi Double-Entry (`LedgerService`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Core Double-Entry Engine, Validasi Keseimbangan, & Penyesuaian Saldo Akun  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `LedgerService` yang menjadi jantung pencatatan finansial aplikasi. Setiap transaksi multi-entry (debit & kredit) wajib diproses melalui service ini.
Service ini bertugas:
1. Menjumlahkan seluruh nominal sisi Debit dan seluruh sisi Kredit.
2. Jika $\sum \text{Debit} \ne \sum \text{Kredit}$, melempar `UnbalancedLedgerException` dan me-rollback transaksi secara total (tidak ada data yang tertinggal).
3. Jika seimbang, membungkus eksekusi dalam `DB::transaction()`:
   - Membuat record induk `Transaction`.
   - Membuat baris-baris `LedgerEntry`.
   - Menyesuaikan kolom `current_balance` pada masing-masing akun sesuai aturan akuntansi:
     - Akun Aset (`bank`, `ewallet`, `cash`, `receivable`): Debit menambah saldo ($+$), Kredit mengurangi saldo ($-$).
     - Akun Kewajiban/Ekuitas (`debt`, `income`): Kredit menambah saldo ($+$), Debit mengurangi saldo ($-$).
     - Akun Beban (`expense`): Debit menambah akumulasi beban ($+$).

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/LedgerService.php` — Core service ledger.
2. `tests/Feature/Services/LedgerServiceTest.php` — Automated test feature pengujian double-entry balance.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/LedgerService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Exceptions\UnbalancedLedgerException;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * @param array $entries Array of ['account_id' => int, 'entry_type' => EntryType, 'amount' => int]
     */
    public function recordTransaction(
        WorkDay $workDay,
        string $type,
        string $description,
        Carbon $transactionTime,
        array $entries,
        bool $isEstimatedTime = false,
        ?array $metadata = null
    ): Transaction {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($entries as $entry) {
            if ($entry['entry_type'] === EntryType::DEBIT) {
                $totalDebit += $entry['amount'];
            } else {
                $totalCredit += $entry['amount'];
            }
        }

        if ($totalDebit !== $totalCredit) {
            throw new UnbalancedLedgerException($totalDebit, $totalCredit);
        }

        return DB::transaction(function () use ($workDay, $type, $description, $transactionTime, $entries, $isEstimatedTime, $metadata) {
            $transaction = Transaction::create([
                'work_day_id' => $workDay->id,
                'type' => $type,
                'description' => $description,
                'transaction_time' => $transactionTime,
                'is_estimated_time' => $isEstimatedTime,
                'metadata' => $metadata,
            ]);

            foreach ($entries as $entry) {
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $entry['account_id'],
                    'entry_type' => $entry['entry_type'],
                    'amount' => $entry['amount'],
                    'created_at' => now(),
                ]);

                $account = Account::lockForUpdate()->find($entry['account_id']);
                $this->updateAccountBalance($account, $entry['entry_type'], $entry['amount']);
            }

            return $transaction->load('ledgerEntries');
        });
    }

    protected function updateAccountBalance(Account $account, EntryType $entryType, int $amount): void
    {
        $isAsset = $account->type->isAsset();

        if ($isAsset) {
            $delta = ($entryType === EntryType::DEBIT) ? $amount : -$amount;
        } else {
            // Expense / Debt / Income
            $delta = ($entryType === EntryType::DEBIT) ? $amount : -$amount;
        }

        $account->current_balance += $delta;
        $account->save();
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Transaksi seimbang berhasil membuat header transaksi, entri mutasi, dan meng-update saldo akun secara tepat hingga Rp1.
2. Transaksi tidak seimbang melempar `UnbalancedLedgerException` dan database rollback bersih.
3. Seluruh proses penguncian baris (`lockForUpdate`) mencegah race condition saat saldo diubah bersamaan.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/LedgerServiceTest.php`
- **Metode Uji:**
  1. `test_record_transaction_persists_records_and_updates_account_balances_correctly()`:
     - Mutasi Debit Kas 10.000, Kredit BCA 10.000 $\rightarrow$ Saldo Kas bertambah 10.000, Saldo BCA berkurang 10.000.
  2. `test_unbalanced_entries_throw_exception_and_rollback_cleanly()`:
     - Mutasi Debit 50.000, Kredit 40.000 $\rightarrow$ exception muncul, saldo akun sama sekali tidak berubah.
  3. `test_concurrency_race_condition_handled_safely()`:
     - Memastikan penggunaan database transaction melindungi presisi uang.
