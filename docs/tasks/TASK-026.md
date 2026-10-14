# TASK-026: Model Eloquent `DriverSnapshot` (Accessors Total Pendapatan & Relasi Domain)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** Model Domain Snapshot Driver dengan Auto-Kalkulasi Pendapatan & Saldo Digital  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Model Eloquent `DriverSnapshot` yang menghubungkan tabel `driver_snapshots` ke aplikasi.
Model ini wajib menyediakan:
1. Relasi domain:
   - `belongsTo(WorkDay::class)`: Mengaitkan snapshot ke hari kerja aktif.
2. Property casting:
   - Seluruh kolom uang di-cast ke `integer`.
   - `recorded_at` di-cast ke `datetime`.
3. Computed Accessors (Atribut Terhitung Otomatis):
   - `total_driver_income`: Menjumlahkan $\text{hub\_income} + \text{hub\_bonus} + \text{customer\_tips}$.
   - `total_digital_holding`: Menjumlahkan $\text{shopeepay\_balance} + \text{gopay\_balance} + \text{gopay\_merchant\_balance}$.
   - `merchant_settlement_status`: Mengembalikan string `'ready_to_settle'` jika $\text{gopay\_merchant\_balance} \ge 10000$, atau `'holding'` jika $< 10000$.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Models/DriverSnapshot.php` — Model Eloquent `DriverSnapshot`.
2. `tests/Unit/Models/DriverSnapshotModelTest.php` — Automated test verifikasi relasi dan accessors.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/DriverSnapshot.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_day_id',
        'recorded_at',
        'completed_orders',
        'hub_income',
        'hub_bonus',
        'customer_tips',
        'shopeepay_balance',
        'gopay_balance',
        'gopay_merchant_balance',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'completed_orders' => 'integer',
        'hub_income' => 'integer',
        'hub_bonus' => 'integer',
        'customer_tips' => 'integer',
        'shopeepay_balance' => 'integer',
        'gopay_balance' => 'integer',
        'gopay_merchant_balance' => 'integer',
    ];

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }

    public function totalDriverIncome(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hub_income + $this->hub_bonus + $this->customer_tips
        );
    }

    public function totalDigitalHolding(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->shopeepay_balance + $this->gopay_balance + $this->gopay_merchant_balance
        );
    }

    public function merchantSettlementStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->gopay_merchant_balance >= 10000 ? 'ready_to_settle' : 'holding'
        );
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Model `DriverSnapshot` tersimpan di namespace `App\Models`.
2. Accessor `$snapshot->total_driver_income` menjumlahkan 3 komponen ShopeeFood secara presisi.
3. Accessor `$snapshot->total_digital_holding` menjumlahkan 3 rekening digital secara presisi.
4. Accessor `$snapshot->merchant_settlement_status` mengembalikan status yang akurat berdasarkan ambang batas Rp10.000.
5. Unit tests lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/DriverSnapshotModelTest.php`
- **Metode Uji:**
  1. `test_total_driver_income_sums_hub_income_bonus_and_tips()`:
     - Hub 150k + Bonus 14.5k + Tips 10k $\rightarrow$ Total Rp174.500.
  2. `test_merchant_settlement_status_identifies_holding_vs_ready()`:
     - Saldo Merchant 8.500 $\rightarrow$ `'holding'`.
     - Saldo Merchant 12.000 $\rightarrow$ `'ready_to_settle'`.
  3. `test_snapshot_belongs_to_work_day()`:
     - Menguji integritas relasi ke `WorkDay`.
