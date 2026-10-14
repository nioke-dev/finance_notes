# TASK-039: Service Pesanan COD & Deteksi Surplus Tip Cash (`CodOrderService`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Logika Alur Finansial COD (Tagihan Riil vs Uang Diterima & Alokasi Surplus Cash)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `CodOrderService` yang menangani transaksi pengantaran pesanan Cash On Delivery (COD) makanan di tempat pelanggan.
Sesuai aturan bisnis resmi di `BUSINESS-RULES.md`:
1. **Skenario Lapangan Nyata:**
   - Tagihan COD makanan di aplikasi: Rp15.125.
   - Pelanggan menyerahkan uang kertas pecahan Rp16.000 (atau Rp20.000) dan mengatakan: *"Kembaliannya ambil saja, Mas"*.
   - Ada kelebihan uang tunai (*surplus*) sebesar: $\text{Uang Diterima} - \text{Tagihan COD} = \text{Rp}16.000 - \text{Rp}15.125 = \text{Rp}875$.
2. **Aturan Akuntansi Multi-Split:**
   - Uang fisik yang masuk ke kantong/dompet driver (`Cash`) adalah **Rp16.000 penuh** (Debit Cash).
   - Tagihan yang disetorkan/ditalangi driver ke Shopee adalah **Rp15.125** (Kredit Kas Talangan / Settlement ShopeeFood).
   - Selisih surplus **Rp875** dicatat sebagai akun pendapatan terpisah: `Tip Cash / Surplus COD` (Kredit).
   - Surplus ini **BUKAN** pendapatan pesanan resmi ShopeeFood, sehingga tidak boleh tercampur dengan Pendapatan Hub.
   *Keseimbangan: Debit Cash (16.000) = Kredit Talangan (15.125) + Kredit Surplus Tip (875).*

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/CodOrderService.php` — Service transaksi COD.
2. `tests/Feature/Services/CodOrderServiceTest.php` — Automated test feature pengujian multi-split ledger COD.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/CodOrderService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CodOrderService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function recordCodOrder(
        WorkDay $workDay,
        int $billAmount,
        int $receivedAmount,
        ?string $orderNumber = null,
        ?Carbon $transactionTime = null
    ): Transaction {
        if ($billAmount <= 0) {
            throw new InvalidArgumentException("Tagihan COD harus lebih besar dari Rp0.");
        }

        if ($receivedAmount < $billAmount) {
            throw new InvalidArgumentException("Uang tunai diterima tidak boleh kurang dari nominal tagihan COD.");
        }

        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $codSettlementAccount = Account::where('name', 'ShopeePay')->firstOrFail(); // Atau akun talangan driver
        $tipIncomeAccount = Account::where('name', 'Tip Cash / Surplus COD')->firstOrFail();

        $surplus = $receivedAmount - $billAmount;
        $orderLabel = $orderNumber ? " Pesanan #{$orderNumber}" : "";
        $description = "Penyelesaian COD{$orderLabel}: Tagihan Rp{$billAmount}, Terima Rp{$receivedAmount}";

        $entries = [];

        // 1. Debit Cash penuh sebesar uang riil yang masuk kantong
        $entries[] = [
            'account_id' => $cashAccount->id,
            'entry_type' => EntryType::DEBIT,
            'amount' => $receivedAmount,
        ];

        // 2. Kredit akun penampung tagihan
        $entries[] = [
            'account_id' => $codSettlementAccount->id,
            'entry_type' => EntryType::CREDIT,
            'amount' => $billAmount,
        ];

        // 3. Kredit akun surplus tip jika ada kelebihan uang
        if ($surplus > 0) {
            $entries[] = [
                'account_id' => $tipIncomeAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $surplus,
            ];
        }

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'income',
            description: $description,
            transactionTime: $transactionTime ?? now(),
            entries: $entries,
            isEstimatedTime: false,
            metadata: [
                'type' => 'cod_order',
                'order_number' => $orderNumber,
                'bill_amount' => $billAmount,
                'received_amount' => $receivedAmount,
                'surplus_amount' => $surplus,
            ]
        );
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Transaksi COD dengan uang pas (Tagihan 15k, Terima 15k) mencatat surplus Rp0.
2. Transaksi COD dengan uang lebih (Tagihan 15.125, Terima 16.000) menambah akun `Cash` Rp16.000 dan menambah akun `Tip Cash / Surplus COD` Rp875 secara presisi.
3. Transaksi dengan uang diterima kurang dari tagihan melempar `InvalidArgumentException`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/CodOrderServiceTest.php`
- **Metode Uji:**
  1. `test_cod_exact_amount_records_zero_surplus()`:
     - Tagihan 20.000 diterima 20.000 $\rightarrow$ Surplus Rp0.
  2. `test_cod_with_extra_cash_allocates_difference_to_tip_income_account()`:
     - Tagihan 15.125 diterima 16.000 $\rightarrow$ Saldo Kas naik 16k, Surplus Tip naik Rp875.
  3. `test_cod_fails_if_received_amount_less_than_bill()`:
     - Tagihan 20k diterima 15k $\rightarrow$ exception muncul.
