# TASK-050: Model Eloquent `CashDenomination` & Kalkulator Otomatis Akumulasi Fisik

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Model Domain Denominasi dengan Formula Kalkulasi Matematis Uang Fisik  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Model Eloquent `CashDenomination` yang menghubungkan data fisik uang tunai ke sistem.
Model ini wajib menyediakan:
1. Relasi domain:
   - `belongsTo(WorkDay::class)`: Relasi 1-to-1 ke hari kerja terkait.
2. Property casting:
   - Seluruh kolom kuantitas koin dan kertas di-cast ke `integer`.
   - Kolom `total_physical_amount` di-cast ke `integer`.
3. Helper method statis & dinamis `calculateTotal(array $quantities): int`:
   - Menghitung total fisik secara otomatis berdasarkan rumus nilai pecahan:
   $$\text{Total} = (100 \times c_{100}) + (200 \times c_{200}) + (500 \times c_{500}) + (1000 \times c_{1000}) + (1000 \times b_{1000}) + (2000 \times b_{2000}) + (5000 \times b_{5000}) + (10000 \times b_{10000}) + (20000 \times b_{20000}) + (50000 \times b_{50000}) + (100000 \times b_{100000})$$
4. Event model `saving`:
   - Otomatis menghitung dan memperbarui kolom `total_physical_amount` setiap kali record disimpan atau diperbarui.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Models/CashDenomination.php` — Model Eloquent.
2. `tests/Unit/Models/CashDenominationModelTest.php` — Automated test verifikasi model dan akurasi rumus hitung fisik.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/CashDenomination.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDenomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_day_id',
        'coin_100',
        'coin_200',
        'coin_500',
        'coin_1000',
        'bill_1000',
        'bill_2000',
        'bill_5000',
        'bill_10000',
        'bill_20000',
        'bill_50000',
        'bill_100000',
        'total_physical_amount',
    ];

    protected $casts = [
        'coin_100' => 'integer',
        'coin_200' => 'integer',
        'coin_500' => 'integer',
        'coin_1000' => 'integer',
        'bill_1000' => 'integer',
        'bill_2000' => 'integer',
        'bill_5000' => 'integer',
        'bill_10000' => 'integer',
        'bill_20000' => 'integer',
        'bill_50000' => 'integer',
        'bill_100000' => 'integer',
        'total_physical_amount' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashDenomination $model) {
            $model->total_physical_amount = self::calculateSum($model->toArray());
        });
    }

    public static function calculateSum(array $data): int
    {
        return (100 * ($data['coin_100'] ?? 0))
            + (200 * ($data['coin_200'] ?? 0))
            + (500 * ($data['coin_500'] ?? 0))
            + (1000 * ($data['coin_1000'] ?? 0))
            + (1000 * ($data['bill_1000'] ?? 0))
            + (2000 * ($data['bill_2000'] ?? 0))
            + (5000 * ($data['bill_5000'] ?? 0))
            + (10000 * ($data['bill_10000'] ?? 0))
            + (20000 * ($data['bill_20000'] ?? 0))
            + (50000 * ($data['bill_50000'] ?? 0))
            + (100000 * ($data['bill_100000'] ?? 0));
    }

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Model `CashDenomination` tersimpan di namespace `App\Models`.
2. Method `calculateSum` menghitung total uang fisik secara presisi tanpa pembulatan.
3. Event `saving` otomatis mengisi `total_physical_amount` saat model disimpan.
4. Relasi `$denomination->workDay` berjalan sempurna.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/CashDenominationModelTest.php`
- **Metode Uji:**
  1. `test_calculate_sum_returns_exact_currency_total()`:
     - 4 koin 500 (2.000) + 5 lembar 2.000 (10.000) + 2 lembar 50.000 (100.000) $\rightarrow$ Total Rp112.000 tepat.
  2. `test_saving_event_automatically_updates_total_physical_amount()`:
     - Menyimpan model tanpa mengisi `total_physical_amount` $\rightarrow$ kolom terisi otomatis Rp112.000.
