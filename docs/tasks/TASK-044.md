# TASK-044: Service Pencairan Pinjaman Dana Masuk (`LoanDisbursementService`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Logika Pencairan Bersih Kas vs Biaya Provisi Pinjaman di Muka  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `LoanDisbursementService` yang menangani penerimaan pencairan pinjaman baru (contoh: GoPay Pinjam atau SeaBank Pinjam).
Sesuai aturan bisnis resmi di `PRD.md`:
1. **Skenario Lapangan Nyata:**
   - Driver mengajukan pinjaman Rp600.000 dengan biaya administrasi/provisi Rp100.000 yang dipotong di muka.
   - Uang tunai/transfer bersih yang benar-benar masuk ke rekening driver (misal BCA) adalah **Rp500.000**.
   - Total kewajiban utang yang harus dibayar driver tetap **Rp600.000**.
2. **Aturan Akuntansi Multi-Split:**
   - Debit Akun Bank Tujuan (`BCA`): Rp500.000 (Kas riil bertambah sebesar dana bersih).
   - Debit Akun Beban (`Beban Biaya Admin & Provisi Pinjaman`): Rp100.000 (Biaya pinjaman tercatat).
   - Kredit Akun Kewajiban (`Kewajiban Utang Pinjaman`): Rp600.000 (Utang tercatat penuh).
   *Keseimbangan: Debit Kas (500k) + Debit Beban (100k) = Kredit Kewajiban (600k).*
3. Menghasilkan jadwal cicilan otomatis di tabel `debt_installments` berdasarkan tenor bulan yang diajukan.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/LoanDisbursementService.php` — Service pencairan pinjaman.
2. `tests/Feature/Services/LoanDisbursementServiceTest.php` — Automated test feature pencairan pinjaman dan pembuatan jadwal angsuran.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/LoanDisbursementService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanDisbursementService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function disburseLoan(
        WorkDay $workDay,
        Debt $debt,
        Account $destinationAccount,
        int $netReceivedAmount,
        int $feeAmount,
        Carbon $disbursedAt,
        int $dueDayOfMonth = 5
    ): Debt {
        if ($netReceivedAmount + $feeAmount !== $debt->total_obligation) {
            throw new InvalidArgumentException(
                "Jumlah dana bersih (Rp{$netReceivedAmount}) + biaya (Rp{$feeAmount}) harus sama dengan total kewajiban utang (Rp{$debt->total_obligation})."
            );
        }

        return DB::transaction(function () use ($workDay, $debt, $destinationAccount, $netReceivedAmount, $feeAmount, $disbursedAt, $dueDayOfMonth) {
            $feeExpenseAccount = Account::where('name', 'Beban Biaya Admin Transfer')->firstOrFail(); // Atau pos biaya pinjaman
            $debtLiabilityAccount = Account::where('name', 'BCA')->firstOrFail(); // Akun penyeimbang kewajiban

            $entries = [];

            // 1. Debit rekening bank penerima sebesar dana bersih
            $entries[] = [
                'account_id' => $destinationAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $netReceivedAmount,
            ];

            // 2. Debit biaya provisi jika > 0
            if ($feeAmount > 0) {
                $entries[] = [
                    'account_id' => $feeExpenseAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $feeAmount,
                ];
            }

            // 3. Kredit akun kewajiban sebesar total utang
            $entries[] = [
                'account_id' => $debtLiabilityAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $debt->total_obligation,
            ];

            $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'transfer',
                description: "Pencairan Pinjaman: {$debt->name} (Terima Bersih Rp{$netReceivedAmount})",
                transactionTime: $disbursedAt,
                entries: $entries,
                isEstimatedTime: false,
                metadata: [
                    'debt_id' => $debt->id,
                    'net_received' => $netReceivedAmount,
                    'fee' => $feeAmount,
                ]
            );

            // Buat jadwal cicilan otomatis
            $monthlyAmount = intdiv($debt->total_obligation, $debt->tenor_months);
            for ($i = 1; $i <= $debt->tenor_months; $i++) {
                $dueDate = $disbursedAt->copy()->addMonths($i)->setDay(min($dueDayOfMonth, 28));

                DebtInstallment::create([
                    'debt_id' => $debt->id,
                    'installment_number' => $i,
                    'due_date' => $dueDate,
                    'amount_due' => $monthlyAmount,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                ]);
            }

            return $debt->fresh()->load('installments');
        });
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Saldo akun penerima (`destinationAccount`) hanya bertambah sebesar dana bersih yang diterima.
2. Seluruh kewajiban pinjaman tercatat penuh secara seimbang di ledger.
3. Menghasilkan jadwal angsuran di tabel `debt_installments` sebanyak `tenor_months`.
4. Jika $\text{netReceived} + \text{fee} \ne \text{totalObligation}$, transaksi melempar `InvalidArgumentException`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/LoanDisbursementServiceTest.php`
- **Metode Uji:**
  1. `test_loan_disbursement_credits_net_amount_to_destination_account()`:
     - Pinjam 600k biaya 100k bersih 500k $\rightarrow$ Rekening BCA bertambah Rp500.000.
  2. `test_loan_disbursement_creates_exact_number_of_installments()`:
     - Tenor 3 bulan $\rightarrow$ Tepat tercipta 3 row di `debt_installments`.
  3. `test_mismatched_amounts_throw_invalid_argument_exception()`:
     - Input nominal yang tidak seimbang dengan total kewajiban $\rightarrow$ exception muncul.
