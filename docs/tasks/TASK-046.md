# TASK-046: Service Pelunasan Utang Teman & Auto-Sedekah Sisa Utang (`FriendDebtSettlementService`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Logika Penerimaan Cicilan Teman + Opsi Ikhlaskan Sisa Utang Jadi Sedekah  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `FriendDebtSettlementService` yang menangani skenario pelunasan utang atau talangan teman (misal: Rofik, Arno, Rafsan).
Sesuai aturan bisnis resmi yang dikunci saat tanya jawab di `BUSINESS-RULES.md`:
1. **Skenario Lapangan Nyata:**
   - Teman (Rofik) memiliki sisa utang/piutang: **Rp249.000**.
   - Teman mentransfer ke rekening BCA pengguna sebesar: **Rp248.000** (kurang Rp1.000).
   - Pengguna memilih opsi: `forgive_remainder = true` (*Diikhlaskan / Dianggap Lunas*).
2. **Aturan Akuntansi Multi-Split:**
   - Debit Akun Bank Penerima (`BCA`): Rp248.000 (Kas riil bertambah sesuai uang yang diterima).
   - Debit Akun Beban (`Beban Sedekah & Bantuan Pribadi`): Rp1.000 (Kekurangan yang diikhlaskan dicatat resmi sebagai sedekah).
   - Kredit Akun Piutang Teman (`BCA` / Akun Piutang): Rp249.000 (Piutang teman berkurang penuh Rp249.000).
   *Keseimbangan: Debit Kas (248k) + Debit Sedekah (1k) = Kredit Piutang (249k).*
3. Status utang teman otomatis berubah menjadi `status = 'paid_off'`.
4. Jika `forgive_remainder = false`, sisa Rp1.000 tetap tercatat sebagai saldo sisa piutang aktif.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/FriendDebtSettlementService.php` — Service pelunasan utang teman.
2. `tests/Feature/Services/FriendDebtSettlementServiceTest.php` — Automated test feature multi-split auto-sedekah.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/FriendDebtSettlementService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FriendDebtSettlementService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function settleFriendDebt(
        WorkDay $workDay,
        Debt $debt,
        Account $receivingAccount,
        int $receivedAmount,
        bool $forgiveRemainder = false
    ): Transaction {
        if ($debt->isPaidOff()) {
            throw new InvalidArgumentException("Utang ini sudah lunas sebelumnya.");
        }

        if ($receivedAmount <= 0) {
            throw new InvalidArgumentException("Nominal uang yang diterima harus lebih besar dari Rp0.");
        }

        if ($receivedAmount > $debt->total_obligation) {
            throw new InvalidArgumentException("Uang diterima melebihi total kewajiban utang.");
        }

        return DB::transaction(function () use ($workDay, $debt, $receivingAccount, $receivedAmount, $forgiveRemainder) {
            $charityExpenseAccount = Account::where('name', 'Beban Sedekah & Bantuan Pribadi')->firstOrFail();
            $receivableAccount = Account::where('name', 'BCA')->firstOrFail(); // Akun penyeimbang piutang

            $remainder = $debt->total_obligation - $receivedAmount;
            $isFullySettled = ($remainder === 0) || $forgiveRemainder;

            $entries = [];

            // 1. Debit akun penerima sebesar uang riil masuk
            $entries[] = [
                'account_id' => $receivingAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $receivedAmount,
            ];

            // 2. Jika diikhlaskan dan ada sisa, debit akun beban sedekah
            if ($forgiveRemainder && $remainder > 0) {
                $entries[] = [
                    'account_id' => $charityExpenseAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $remainder,
                ];
            }

            // 3. Kredit akun piutang sebesar total yang ditutup
            $creditedAmount = $forgiveRemainder ? $debt->total_obligation : $receivedAmount;
            $entries[] = [
                'account_id' => $receivableAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $creditedAmount,
            ];

            $transaction = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'income',
                description: "Pelunasan Utang Teman: {$debt->borrower_name} (Terima Rp{$receivedAmount}" . ($forgiveRemainder && $remainder > 0 ? ", Sedekah Rp{$remainder}" : "") . ")",
                transactionTime: now(),
                entries: $entries,
                isEstimatedTime: false,
                metadata: [
                    'debt_id' => $debt->id,
                    'borrower_name' => $debt->borrower_name,
                    'received_amount' => $receivedAmount,
                    'remainder' => $remainder,
                    'forgiven_as_charity' => $forgiveRemainder,
                ]
            );

            if ($isFullySettled) {
                $debt->update(['status' => 'paid_off']);
            }

            return $transaction;
        });
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Pembayaran Rp248.000 untuk utang Rp249.000 dengan `forgiveRemainder = true` menambah kas Rp248k, menambah beban sedekah Rp1k, dan mengubah status utang ke `paid_off`.
2. Pembayaran sebagian dengan `forgiveRemainder = false` mencatat kas masuk tanpa sedekah dan membiarkan utang tetap `active`.
3. Seluruh mutasi debit dan kredit terjaga seimbang hingga Rp1 presisi.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/FriendDebtSettlementServiceTest.php`
- **Metode Uji:**
  1. `test_settle_with_forgiveness_closes_debt_and_records_charity_expense()`:
     - Utang 249k dibayar 248k diikhlaskan $\rightarrow$ Kas +248k, Beban Sedekah +1k, Utang status paid_off.
  2. `test_settle_partial_without_forgiveness_keeps_debt_active()`:
     - Utang 249k dibayar 200k tidak diikhlaskan $\rightarrow$ Utang tetap status active.
