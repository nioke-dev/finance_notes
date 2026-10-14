# TASK-031: Service Quick Parkir Lapangan (`QuickParkingService`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Logika Pemotongan Cash Rp1.000, Alokasi Beban Parkir, & Perekaman Geolocation  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun `QuickParkingService` yang menangani pencatatan pengeluaran parkir motor driver saat on-beat.
Sesuai aturan bisnis resmi di `BUSINESS-RULES.md`:
1. **Nominal Standar:** Parkir motor di Malang mayoritas bertarif tetap Rp1.000 (tunai).
2. **Kecepatan Input:** Driver membutuhkan aksi yang selesai dalam 1 kali tap di aplikasi mobile.
3. **Alur Finansial:**
   - Memotong saldo akun `Cash` tepat Rp1.000 (Kredit).
   - Menambah akun `Beban Parkir` tepat Rp1.000 (Debit).
4. **Data Lokasi & Konteks:**
   - Menyimpan koordinat GPS (`latitude` & `longitude`) jika dikirimkan oleh HP.
   - Kolom nama restoran/lokasi (`restaurant_name`) bersifat opsional (boleh kosong jika driver terburu-buru mengejar waktu masak resto).
   - Waktu transaksi otomatis menggunakan `now()` jika tidak ditentukan.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Services/QuickParkingService.php` — Service quick parkir.
2. `tests/Feature/Services/QuickParkingServiceTest.php` — Automated test feature pengujian pemotongan kas parkir dan metadata GPS.

---

## 3. Spesifikasi Anatomi Kode (`app/Services/QuickParkingService.php`)
```php
namespace App\Services;

use App\Enums\EntryType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkDay;
use Illuminate\Support\Carbon;

class QuickParkingService
{
    public const DEFAULT_PARKING_FEE = 1000;

    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function recordParking(
        WorkDay $workDay,
        int $amount = self::DEFAULT_PARKING_FEE,
        ?string $restaurantName = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?Carbon $transactionTime = null
    ): Transaction {
        $cashAccount = Account::where('name', 'Cash')->firstOrFail();
        $parkingExpenseAccount = Account::where('name', 'Beban Parkir')->firstOrFail();

        $description = $restaurantName 
            ? "Parkir Motor: {$restaurantName}" 
            : "Parkir Motor Lapangan (Quick 1-Tap)";

        $metadata = [
            'type' => 'quick_parking',
            'restaurant_name' => $restaurantName,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        return $this->ledgerService->recordTransaction(
            workDay: $workDay,
            type: 'expense',
            description: $description,
            transactionTime: $transactionTime ?? now(),
            entries: [
                [
                    'account_id' => $cashAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ],
                [
                    'account_id' => $parkingExpenseAccount->id,
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
1. Saldo akun `Cash` berkurang tepat Rp1.000.
2. Saldo akun `Beban Parkir` bertambah tepat Rp1.000.
3. Metadata menyimpan `latitude`, `longitude`, dan `restaurant_name`.
4. Jika `restaurant_name` kosong, deskripsi default terpasang secara rapi tanpa error.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Services/QuickParkingServiceTest.php`
- **Metode Uji:**
  1. `test_record_parking_deducts_cash_and_adds_parking_expense()`:
     - Kas awal Rp50.000 $\rightarrow$ setelah parkir kas menjadi Rp49.000, Beban Parkir bertambah Rp1.000.
  2. `test_record_parking_stores_gps_coordinates_in_metadata()`:
     - Mengirim lat `-7.9666` dan long `112.6326` $\rightarrow$ metadata transaksi memuat koordinat yang sama.
  3. `test_record_parking_succeeds_without_restaurant_name()`:
     - Eksekusi tanpa nama resto tetap sukses dan tersimpan dengan baik.
