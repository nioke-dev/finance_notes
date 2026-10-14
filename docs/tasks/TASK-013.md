# TASK-013: Model Eloquent `Transaction` (Relasi Ledger & Casting Metadata)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Model Domain `Transaction` dengan Relasi ke Hari Kerja & Split Ledger  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Model Eloquent `Transaction` yang merepresentasikan induk transaksi keuangan.
Model ini wajib menyediakan:
1. Relasi domain:
   - `belongsTo(WorkDay::class)`: Mengaitkan transaksi ke hari kerja yang sedang berjalan.
   - `hasMany(LedgerEntry::class)`: Menghubungkan ke seluruh mutasi split debit/kredit yang membentuk transaksi ini.
2. Property casting:
   - `transaction_time` $\rightarrow$ `datetime`
   - `is_estimated_time` $\rightarrow$ `boolean`
   - `metadata` $\rightarrow$ `array` (auto-serialize/deserialize JSON)
3. Query scopes:
   - `scopeIncome(Builder $query)`: Menyaring transaksi pemasukan.
   - `scopeExpense(Builder $query)`: Menyaring transaksi pengeluaran.
   - `scopeTransfer(Builder $query)`: Menyaring transfer antar-rekening sendiri.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Models/Transaction.php` — Model Eloquent `Transaction`.
2. `tests/Unit/Models/TransactionModelTest.php` — Automated test verifikasi perilaku model.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/Transaction.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_day_id',
        'type',
        'description',
        'transaction_time',
        'is_estimated_time',
        'metadata',
    ];

    protected $casts = [
        'transaction_time' => 'datetime',
        'is_estimated_time' => 'boolean',
        'metadata' => 'array',
    ];

    public function workDay(): BelongsTo
    {
        return $this->belongsTo(WorkDay::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('type', 'transfer');
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Model `Transaction` tersimpan di namespace `App\Models`.
2. Relasi `$transaction->workDay` mengembalikan instance `WorkDay`.
3. Relasi `$transaction->ledgerEntries` mengembalikan koleksi `LedgerEntry`.
4. Kolom `metadata` otomatis dikonversi dari/ke array PHP.
5. Unit tests lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/TransactionModelTest.php`
- **Metode Uji:**
  1. `test_transaction_belongs_to_work_day()`:
     - Menguji integritas relasi `workDay`.
  2. `test_transaction_has_many_ledger_entries()`:
     - Menguji integritas relasi `ledgerEntries`.
  3. `test_metadata_casts_to_array_correctly()`:
     - Memastikan input array `['lat' => -7.96, 'long' => 112.63]` tersimpan dan terbaca sebagai array PHP utuh.
