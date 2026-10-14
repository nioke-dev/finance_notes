# TASK-028: Service Otomasi Pengakuan Tip QRIS Lapangan (`AutoQrisTipService`)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** Deteksi Otomatis Kenaikan Saldo GoPay / GoPay Merchant & Pencatatan Pendapatan Tip  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `AutoQrisTipService` yang merealisasikan kesepakatan tanya jawab:
1. Selama on-beat, driver mengisolasi seluruh pengeluaran belanja pribadi lewat rekening BCA (beli minum, rokok, snack).
2. Akun `GoPay` dan `GoPay Merchant` murni digunakan untuk menerima transferan/QRIS dari pelanggan di lapangan (parkir dilebihkan, bayar COD via QRIS, atau tip langsung).
3. Jika saat snapshot dimasukkan ternyata saldo aktual `GoPay` atau `GoPay Merchant` **lebih tinggi** daripada saldo buku tercatat di sistem:
   - Sistem mendeteksi ada dana masuk dari pelanggan.
   - Sistem **SEKETIKA** membuat transaksi `income` otomatis dengan deskripsi *"Pendapatan QRIS / Tip Lapangan"*.
   - Mutasi ledger:
     - Debit: `GoPay` atau `GoPay Merchant` (sebesar selisih kenaikan saldo).
     - Kredit: `Pendapatan QRIS / Tip Non-Tunai` (sebesar selisih kenaikan saldo).
   - Driver **TIDAK PERLU** repot-repot mengetik form atau mengisi nama resto/pelanggan.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/AutoQrisTipService.php` — Service otomasi pengakuan tip QRIS.
2. `tests/Feature/Services/AutoQrisTipServiceTest.php` — Automated test feature pengakuan otomatis tip QRIS.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/AutoQrisTipService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\WorkDay;

class AutoQrisTipService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function detectAndRecordBalanceIncreases(
        WorkDay $workDay,
        int $actualGoPayBalance,
        int $actualMerchantBalance
    ): array {
        $createdTransactions = [];

        $gopayAccount = Account::where('name', 'GoPay')->firstOrFail();
        $merchantAccount = Account::where('name', 'GoPay Merchant')->firstOrFail();
        $tipIncomeAccount = Account::where('name', 'Pendapatan QRIS / Tip Non-Tunai')->firstOrFail();

        // 1. Cek Kenaikan Saldo GoPay
        if ($actualGoPayBalance > $gopayAccount->current_balance) {
            $diffGoPay = $actualGoPayBalance - $gopayAccount->current_balance;

            $createdTransactions[] = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'income',
                description: 'Pendapatan QRIS / Tip Lapangan (Auto-detected via GoPay)',
                transactionTime: now(),
                entries: [
                    ['account_id' => $gopayAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $diffGoPay],
                    ['account_id' => $tipIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $diffGoPay],
                ],
                isEstimatedTime: false,
                metadata: ['source' => 'gopay_auto_tip', 'amount' => $diffGoPay]
            );
        }

        // 2. Cek Kenaikan Saldo GoPay Merchant
        if ($actualMerchantBalance > $merchantAccount->current_balance) {
            $diffMerchant = $actualMerchantBalance - $merchantAccount->current_balance;

            $createdTransactions[] = $this->ledgerService->recordTransaction(
                workDay: $workDay,
                type: 'income',
                description: 'Pendapatan QRIS / Tip Lapangan (Auto-detected via GoPay Merchant)',
                transactionTime: now(),
                entries: [
                    ['account_id' => $merchantAccount->id, 'entry_type' => EntryType::DEBIT, 'amount' => $diffMerchant],
                    ['account_id' => $tipIncomeAccount->id, 'entry_type' => EntryType::CREDIT, 'amount' => $diffMerchant],
                ],
                isEstimatedTime: false,
                metadata: ['source' => 'gopay_merchant_auto_qris', 'amount' => $diffMerchant]
            );
        }

        return $createdTransactions;
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Jika saldo aktual GoPay naik Rp5.000 dari saldo buku, transaksi income otomatis tercatat dan saldo akun GoPay disesuaikan.
2. Jika saldo aktual GoPay Merchant naik Rp12.000 dari saldo buku, transaksi income otomatis tercatat.
3. Jika saldo aktual sama atau lebih kecil dari saldo buku, tidak ada transaksi income yang dibuat (tidak ada false positive).

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/AutoQrisTipServiceTest.php`
- **Metode Uji:**
  1. `test_auto_records_income_when_gopay_balance_increases()`:
     - Saldo buku GoPay 100k, input aktual 115k $\rightarrow$ otomatis tercatat income Rp15.000.
  2. `test_auto_records_income_when_merchant_balance_increases()`:
     - Saldo buku Merchant 0, input aktual 5k $\rightarrow$ otomatis tercatat income Rp5.000.
  3. `test_does_not_create_transaction_when_balances_are_unchanged()`:
     - Saldo sama persis $\rightarrow$ tidak ada transaksi yang dibuat.
