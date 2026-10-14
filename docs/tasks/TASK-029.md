# TASK-029: Service Auto-Settlement GoPay Merchant ke GoPay (`MerchantSettlementService`)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** Aturan Pencairan Saldo QRIS GoPay Merchant (Batas $\ge$ Rp10.000)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `MerchantSettlementService` yang mengimplementasikan mekanisme transfer otomatis (*auto-settlement*) dari GoPay Merchant ke akun GoPay reguler.
Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
1. Uang pembayaran parkir/tip/COD pelanggan melalui QRIS menumpuk di akun `GoPay Merchant`.
2. GoPay Merchant melakukan auto-settle ke akun `GoPay` setiap 1 jam sekali **hanya jika** saldo $\ge$ Rp10.000.
3. **Aturan Evaluasi:**
   - Jika saldo $< \text{Rp}10.000$ (misal Rp8.500): Saldo tertahan (*holding*). Sistem **DILARANG** membuat mutasi transfer palsu.
   - Jika saldo $\ge \text{Rp}10.000$ (misal Rp25.000): Sistem mengeksekusi transfer antar-akun tanpa biaya admin:
     - Kredit: `GoPay Merchant` (sebesar nominal saldo yang disettle).
     - Debit: `GoPay` (sebesar nominal saldo yang disettle).
4. Menghasilkan pencatatan ledger atomik via `LedgerService`.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/MerchantSettlementService.php` — Service aturan settlement GoPay Merchant.
2. `tests/Feature/Services/MerchantSettlementServiceTest.php` — Automated test feature evaluasi settlement.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/MerchantSettlementService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;

class MerchantSettlementService
{
    public const SETTLEMENT_THRESHOLD = 10000;

    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function processSettlementIfEligible(WorkDay $workDay, int $amountToSettle): ?Transaction
    {
        if ($amountToSettle < self::SETTLEMENT_THRESHOLD) {
            return null; // Belum memenuhi batas minimal Rp10.000
        }

        $merchantAccount = Account::where('name', 'GoPay Merchant')->firstOrFail();
        $gopayAccount = Account::where('name', 'GoPay')->firstOrFail();

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'transfer',
            description: "Auto-Settlement GoPay Merchant ke GoPay (Rp{$amountToSettle})",
            transactionTime: now(),
            entries: [
                [
                    'account_id' => $merchantAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amountToSettle,
                ],
                [
                    'account_id' => $gopayAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amountToSettle,
                ],
            ],
            isEstimatedTime: false,
            metadata: [
                'type' => 'merchant_auto_settlement',
                'amount' => $amountToSettle,
            ]
        );
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Saldo di bawah Rp10.000 mengembalikan `null` tanpa ada mutasi saldo.
2. Saldo $\ge$ Rp10.000 memotong saldo `GoPay Merchant` dan menambah saldo `GoPay` secara seimbang.
3. Transaksi settlement tercatat dengan tipe `transfer` tanpa biaya admin.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/MerchantSettlementServiceTest.php`
- **Metode Uji:**
  1. `test_settlement_does_not_execute_when_amount_under_10k()`:
     - Mencoba settle Rp8.500 $\rightarrow$ return null, saldo akun tidak berubah.
  2. `test_settlement_executes_successfully_when_amount_is_10k_or_more()`:
     - Settle Rp15.000 $\rightarrow$ Saldo Merchant berkurang 15k, Saldo GoPay bertambah 15k.
