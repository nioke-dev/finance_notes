# TASK-014: Model Eloquent `LedgerEntry` (Casting Enum & Scopes Debit/Credit)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Model Domain Mutasi Ledger dengan Backed Enum `EntryType`  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Model Eloquent `LedgerEntry` yang merepresentasikan satu baris mutasi debit atau kredit pada akun keuangan.
Model ini wajib menyediakan:
1. Relasi domain:
   - `belongsTo(Transaction::class)`: Terhubung ke header transaksi induk.
   - `belongsTo(Account::class)`: Terhubung ke akun yang saldonya terpengaruh.
2. Property casting:
   - `entry_type` $\rightarrow$ `App\Enums\EntryType::class` (Auto-casting ke backed enum)
   - `amount` $\rightarrow$ `integer` (Satuan Rupiah utuh presisi Rp1)
   - `created_at` $\rightarrow$ `datetime`
3. Non-aktifkan timestamps default (`public $timestamps = false`), karena tabel ini hanya memiliki kolom `created_at` tanpa `updated_at`.
4. Query scopes:
   - `scopeDebits(Builder $query)`: Menyaring mutasi sisi debit.
   - `scopeCredits(Builder $query)`: Menyaring mutasi sisi kredit.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Models/LedgerEntry.php` — Model Eloquent `LedgerEntry`.
2. `tests/Unit/Models/LedgerEntryModelTest.php` — Automated test verifikasi perilaku model.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/LedgerEntry.php`)
```php
namespace App\Models;

use App\Enums\EntryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'entry_type',
        'amount',
        'created_at',
    ];

    protected $casts = [
        'entry_type' => EntryType::class,
        'amount' => 'integer',
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('entry_type', EntryType::DEBIT->value);
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('entry_type', EntryType::CREDIT->value);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Model `LedgerEntry` tersimpan di namespace `App\Models`.
2. Property `$entry->entry_type` otomatis bertipe instance `EntryType`.
3. Property `$entry->amount` bertipe integer murni tanpa float desimal.
4. Relasi `$entry->transaction` dan `$entry->account` berjalan sempurna.
5. Unit tests lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/LedgerEntryModelTest.php`
- **Metode Uji:**
  1. `test_ledger_entry_casts_entry_type_to_enum()`:
     - Menguji nilai string `'debit'` otomatis menjadi `EntryType::DEBIT`.
  2. `test_ledger_entry_amount_is_strictly_cast_to_integer()`:
     - Menguji nominal tersimpan sebagai integer presisi Rp1.
  3. `test_scope_debits_and_credits_filter_correctly()`:
     - Menguji query `LedgerEntry::debits()->get()` hanya mengambil entri debit.
