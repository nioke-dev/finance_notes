# TASK-045: Service SPayLater Skema Promo Diskon 0% & Pelunasan Cicilan (`SPayLaterPaymentService`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Logika Pembayaran Tagihan SPayLater dengan Manfaat Diskon Promo Finansial  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `SPayLaterPaymentService` yang menangani pembayaran tagihan SPayLater.
Sesuai aturan bisnis resmi di `PRD.md` dan `BUSINESS-RULES.md`:
1. **Skenario Lapangan Nyata:**
   - Tagihan SPayLater bulan ini: Rp250.000.
   - Driver membayar tagihan menggunakan promo voucher Shopee/SeaBank sehingga hanya perlu membayar **Rp200.000**.
   - Selisih diskon promo sebesar **Rp50.000** dicatat secara eksplisit sebagai akun pendapatan: `Pendapatan Diskon / Promo Finansial` (bukan pengeluaran normal).
2. **Aturan Akuntansi Multi-Split:**
   - Debit Akun Kewajiban (`Kewajiban SPayLater`): Rp250.000 (Hutang lunas penuh Rp250k).
   - Kredit Akun Kas/Bank (`SeaBank` / `BCA`): Rp200.000 (Kas riil yang keluar).
   - Kredit Akun Pendapatan (`Pendapatan Diskon / Promo Finansial`): Rp50.000 (Manfaat promo finansial).
   *Keseimbangan: Debit Kewajiban (250k) = Kredit Kas Keluar (200k) + Kredit Pendapatan Diskon (50k).*
3. Status angsuran di tabel `debt_installments` berubah menjadi `status = 'paid'` dan mencatat `paid_at = now()`.
4. Jika seluruh cicilan pada utang induk tersebut telah lunas, status `debts.status` otomatis berubah menjadi `paid_off`.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/SPayLaterPaymentService.php` — Service pembayaran SPayLater.
2. `tests/Feature/Services/SPayLaterPaymentServiceTest.php` — Automated test feature pembayaran angsuran dengan promo diskon.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/SPayLaterPaymentService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SPayLaterPaymentService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function payInstallment(
        WorkDay $workDay,
        DebtInstallment $installment,
        Account $paymentAccount,
        int $actualPaidAmount,
        int $promoDiscountAmount = 0
    ): Transaction {
        if ($installment->isPaid()) {
            throw new InvalidArgumentException("Cicilan ini sudah pernah dibayar lunas.");
        }

        $totalSettlement = $actualPaidAmount + $promoDiscountAmount;
        if ($totalSettlement !== $installment->amount_due) {
            throw new InvalidArgumentException(
                "Total pembayaran (Rp{$actualPaidAmount}) + diskon promo (Rp{$promoDiscountAmount}) harus sama dengan tagihan cicilan (Rp{$installment->amount_due})."
            );
        }

        return DB::transaction(function () use ($workDay, $installment, $paymentAccount, $actualPaidAmount, $promoDiscountAmount) {
            $spaylaterLiabilityAccount = Account::where('name', 'BCA')->firstOrFail(); // Atau pos kewajiban utang
            $discountIncomeAccount = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->firstOrFail(); // Atau akun pendapatan promo

            $entries = [];

            // 1. Debit akun kewajiban sebesar tagihan utuh
            $entries[] = [
                'account_id' => $spaylaterLiabilityAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $installment->amount_due,
            ];

            // 2. Kredit akun bank/kas pembayar sebesar kas riil keluar
            $entries[] = [
                'account_id' => $paymentAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $actualPaidAmount,
            ];

            // 3. Kredit akun pendapatan diskon jika ada promo > 0
            if ($promoDiscountAmount > 0) {
                $entries[] = [
                    'account_id' => $discountIncomeAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $promoDiscountAmount,
                ];
            }

            $transaction = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'expense',
                description: "Bayar SPayLater: {$installment->debt->name} (Cicilan #{$installment->installment_number})",
                transactionTime: now(),
                entries: $entries,
                isEstimatedTime: false,
                metadata: [
                    'debt_id' => $installment->debt_id,
                    'installment_id' => $installment->id,
                    'actual_paid' => $actualPaidAmount,
                    'promo_discount' => $promoDiscountAmount,
                ]
            );

            $installment->update([
                'amount_paid' => $actualPaidAmount,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Cek apakah seluruh cicilan pada utang ini sudah lunas
            $hasUnpaid = DebtInstallment::where('debt_id', $installment->debt_id)->where('status', 'unpaid')->exists();
            if (!$hasUnpaid) {
                $installment->debt->update(['status' => 'paid_off']);
            }

            return $transaction;
        });
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Pembayaran tagihan Rp250.000 dengan kas Rp200.000 dan diskon Rp50.000 sukses dieksekusi secara seimbang.
2. Saldo akun pembayar (`SeaBank`/`BCA`) hanya berkurang sebesar kas riil (Rp200.000).
3. Status angsuran berubah menjadi `paid` dan utang induk otomatis `paid_off` jika semua cicilan lunas.
4. Mencegah pembayaran ulang pada cicilan yang sudah lunas.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/SPayLaterPaymentServiceTest.php`
- **Metode Uji:**
  1. `test_pay_installment_with_promo_discount_balances_ledger()`:
     - Tagihan 250k dibayar 200k diskon 50k $\rightarrow$ Saldo bank berkurang 200k, cicilan status paid.
  2. `test_paying_last_installment_updates_parent_debt_status_to_paid_off()`:
     - Melunasi cicilan terakhir mengubah status utang menjadi `paid_off`.
  3. `test_cannot_pay_already_paid_installment()`:
     - Membayar cicilan lunas melempar `InvalidArgumentException`.
