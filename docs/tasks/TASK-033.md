# TASK-033: Service Quick Bensin Lapangan (`QuickFuelService`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Auto-Kalkulasi Volume Liter (SPBU Rp10k vs Madura Rp12k) & Pemotongan Akun Bayar  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `QuickFuelService` yang menangani pencatatan pengisian bensin motor driver setelah atau di sela waktu kerja.
Sesuai aturan bisnis resmi di `BUSINESS-RULES.md`:
1. Driver **TIDAK PERLU** menghitung atau mengetik jumlah liter yang dibeli.
2. Driver cukup memilih tipe tempat/vendor via 1-tap:
   - `spbu`: SPBU Pertamina reguler (patokan harga Rp10.000 / liter).
   - `madura`: Warung eceran Madura / Pertamini (patokan harga Rp12.000 / botol ~ 1 liter).
3. **Kalkulasi Volume Otomatis:**
   $$\text{Estimasi Liter SPBU} = \frac{\text{Nominal Bayar}}{10.000}, \quad \text{Estimasi Botol Madura} = \frac{\text{Nominal Bayar}}{12.000}$$
4. **Alur Finansial:**
   - Memotong akun sumber yang dipilih driver (biasanya `Cash` atau `BCA`).
   - Menambah akun `Beban Bensin Kendaraan` (Debit).
   - Metadata transaksi menyimpan `vendor_type`, `unit_price`, dan `estimated_volume`.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/QuickFuelService.php` — Service quick bensin.
2. `tests/Feature/Services/QuickFuelServiceTest.php` — Automated test feature kalkulasi liter dan pemotongan saldo.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/QuickFuelService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class QuickFuelService
{
    public const PRICE_SPBU = 10000;
    public const PRICE_MADURA = 12000;

    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function recordFuel(
        WorkDay $workDay,
        Account $sourceAccount,
        string $vendorType,
        int $amount,
        ?Carbon $transactionTime = null
    ): Transaction {
        $vendor = strtolower(trim($vendorType));
        if (!in_array($vendor, ['spbu', 'madura'])) {
            throw new InvalidArgumentException("Tipe vendor bensin harus 'spbu' atau 'madura'.");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException("Nominal pembelian bensin harus lebih besar dari Rp0.");
        }

        $unitPrice = ($vendor === 'spbu') ? self::PRICE_SPBU : self::PRICE_MADURA;
        $estimatedVolume = round($amount / $unitPrice, 2);

        $fuelExpenseAccount = Account::where('name', 'Beban Bensin Kendaraan')->firstOrFail();

        $vendorLabel = ($vendor === 'spbu') ? 'SPBU' : 'Warung Madura';
        $description = "Beli Bensin ({$vendorLabel}): Rp" . number_format($amount, 0, ',', '.') . " (~{$estimatedVolume} L)";

        $metadata = [
            'type' => 'quick_fuel',
            'vendor_type' => $vendor,
            'unit_price' => $unitPrice,
            'estimated_volume' => $estimatedVolume,
        ];

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $description,
            transactionTime: $transactionTime ?? now(),
            entries: [
                [
                    'account_id' => $sourceAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ],
                [
                    'account_id' => $fuelExpenseAccount->id,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amount,
                ],
            ],
            isEstimatedTime: false,
            metadata: $metadata
        );
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Pembelian bensin Rp30.000 di SPBU menghasilkan estimasi `3.0` liter di metadata.
2. Pembelian bensin Rp24.000 di Madura menghasilkan estimasi `2.0` liter di metadata.
3. Saldo akun sumber bayar (Cash/BCA) terpotong secara tepat.
4. Nilai vendor selain `'spbu'` atau `'madura'` melempar `InvalidArgumentException`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/QuickFuelServiceTest.php`
- **Metode Uji:**
  1. `test_record_fuel_spbu_calculates_correct_liters_and_deducts_source()`:
     - Beli Rp30k SPBU via BCA $\rightarrow$ BCA berkurang Rp30k, metadata volume = 3.0.
  2. `test_record_fuel_madura_calculates_correct_volume_and_deducts_cash()`:
     - Beli Rp24k Madura via Cash $\rightarrow$ Cash berkurang Rp24k, metadata volume = 2.0.
  3. `test_invalid_vendor_type_throws_exception()`:
     - Kirim vendor `'shell'` $\rightarrow$ melempar InvalidArgumentException.
