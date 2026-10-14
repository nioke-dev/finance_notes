# TASK-017: Service Transfer Antar-Akun Sendiri & Alokasi Biaya Admin (`TransferService`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Logika Transfer Antar-Aset Sendiri (Top-Up / Tarik Tunai) & Multi-Split Admin Fee  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `TransferService` yang menangani pemindahan dana antar-akun aset milik pengguna (misal: Top-Up ShopeePay dari BCA, Tarik Tunai BCA ke Cash, atau pemindahan saldo GoPay ke Bank).
Sesuai aturan bisnis resmi di `BUSINESS-RULES.md`:
1. Transfer antar-akun sendiri **BUKAN** pendapatan dan **BUKAN** pengeluaran.
2. Jika ada biaya admin transaksi (misalnya Rp1.000 atau Rp500):
   - Biaya admin dicatat secara spesifik sebagai **Beban Biaya Admin Transfer** (Pengeluaran).
   - Akun sumber dipotong sebesar: $\text{Nominal Transfer} + \text{Biaya Admin}$.
   - Akun tujuan bertambah sebesar: $\text{Nominal Transfer}$.
3. Menggunakan `LedgerService` yang dibuat pada `TASK-016` untuk menjamin atomisitas double-entry:
   - Kredit Akun Asal: $(\text{Nominal} + \text{Admin})$
   - Debit Akun Tujuan: $\text{Nominal}$
   - Debit Akun Beban Admin: $\text{Admin}$
   *Keseimbangan: Kredit = Debit Tujuan + Debit Admin.*

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/TransferService.php` — Service transfer antar-akun.
2. `tests/Feature/Services/TransferServiceTest.php` — Automated test feature logika transfer dan pemotongan biaya admin.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/TransferService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class TransferService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function transfer(
        WorkDay $workDay,
        Account $sourceAccount,
        Account $destinationAccount,
        int $amount,
        int $adminFee = 0,
        ?Carbon $transactionTime = null,
        ?string $note = null
    ): Transaction {
        if ($sourceAccount->id === $destinationAccount->id) {
            throw new InvalidArgumentException("Akun sumber dan akun tujuan tidak boleh sama.");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException("Nominal transfer harus lebih besar dari Rp0.");
        }

        if ($adminFee < 0) {
            throw new InvalidArgumentException("Biaya admin tidak boleh negatif.");
        }

        $time = $transactionTime ?? now();
        $description = $note ?? "Transfer dari {$sourceAccount->name} ke {$destinationAccount->name}";

        $entries = [];

        // 1. Kredit akun sumber (nominal + admin)
        $totalDeduction = $amount + $adminFee;
        $entries[] = [
            'account_id' => $sourceAccount->id,
            'entry_type' => EntryType::CREDIT,
            'amount' => $totalDeduction,
        ];

        // 2. Debit akun tujuan (nominal transfer murni)
        $entries[] = [
            'account_id' => $destinationAccount->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => $amount,
        ];

        // 3. Debit akun beban admin (jika ada admin fee > 0)
        if ($adminFee > 0) {
            $adminExpenseAccount = Account::where('name', 'Beban Biaya Admin Transfer')->firstOrFail();
            $entries[] = [
                'account_id' => $adminExpenseAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $adminFee,
            ];
        }

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'transfer',
            description: $description,
            transactionTime: $time,
            entries: $entries,
            isEstimatedTime: false,
            metadata: [
                'from_account_id' => $sourceAccount->id,
                'to_account_id' => $destinationAccount->id,
                'admin_fee' => $adminFee,
            ]
        );
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Transfer tanpa biaya admin memindahkan saldo asal ke saldo tujuan tepat 100% tanpa selisih.
2. Transfer dengan biaya admin memotong akun asal sebesar $\text{nominal} + \text{admin}$, dan menambah saldo akun beban admin sebesar nominal admin.
3. Transfer ke akun yang sama melempar `InvalidArgumentException`.
4. Nominal transfer $\le 0$ melempar `InvalidArgumentException`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/TransferServiceTest.php`
- **Metode Uji:**
  1. `test_transfer_without_admin_fee_moves_exact_balance_between_accounts()`:
     - Transfer BCA $\rightarrow$ ShopeePay Rp100.000 $\rightarrow$ Saldo BCA berkurang 100k, ShopeePay bertambah 100k.
  2. `test_transfer_with_admin_fee_deducts_source_and_records_admin_expense()`:
     - Transfer BCA $\rightarrow$ ShopeePay Rp100.000 dengan admin Rp1.000 $\rightarrow$ BCA berkurang 101k, ShopeePay bertambah 100k, Beban Admin bertambah 1k.
  3. `test_cannot_transfer_to_the_same_account()`:
     - Transfer BCA ke BCA melempar exception.
