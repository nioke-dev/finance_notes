# TASK-010: Model Eloquent `WorkDay` (Helper Status, Scopes Tanggal Gantung, & Mutator)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Model Domain `WorkDay` dengan Helper Lifecycle Closing  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Model Eloquent `WorkDay` yang merepresentasikan siklus hidup satu hari kerja pengemudi ShopeeFood.
Model ini wajib menyediakan:
1. Enum / konstanta status baku: `STATUS_NEEDS_RECONCILIATION = 'needs_reconciliation'`, `STATUS_FINAL = 'final'`.
2. Helper method boolean:
   - `isFinal(): bool`: Menandakan hari kerja telah ditutup permanen.
   - `needsReconciliation(): bool`: Menandakan hari kerja masih berupa draft aktif atau menunggu penyelesaian selisih.
3. Query scopes:
   - `scopeUnfinalized(Builder $query)`: Menyaring hari-hari yang masih menggantung untuk keperluan Banner Pengingat di aplikasi Mobile.
   - `scopeFinalized(Builder $query)`: Menyaring hari-hari yang telah bersih.
4. Auto-casting tipe data tanggal dan waktu (`work_date` ke `date`, `opened_at` dan `closed_at` ke `datetime`).

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Models/WorkDay.php` — Model Eloquent `WorkDay`.
2. `tests/Unit/Models/WorkDayModelTest.php` — Automated test verifikasi perilaku model.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/WorkDay.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkDay extends Model
{
    use HasFactory;

    public const STATUS_NEEDS_RECONCILIATION = 'needs_reconciliation';
    public const STATUS_FINAL = 'final';

    protected $fillable = [
        'work_date',
        'status',
        'opened_at',
        'closed_at',
        'total_orders',
        'online_hours',
        'is_performance_eligible',
        'closing_notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'total_orders' => 'integer',
        'online_hours' => 'decimal:2',
        'is_performance_eligible' => 'boolean',
    ];

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function needsReconciliation(): bool
    {
        return $this->status === self::STATUS_NEEDS_RECONCILIATION;
    }

    public function scopeUnfinalized(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEEDS_RECONCILIATION);
    }

    public function scopeFinalized(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FINAL);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Model `WorkDay` tersimpan di namespace `App\Models`.
2. Method `isFinal()` mengembalikan `true` hanya jika status adalah `'final'`.
3. Query `WorkDay::unfinalized()->get()` hanya mengambil record dengan status `'needs_reconciliation'`.
4. Casting `is_performance_eligible` bertipe boolean asli.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/WorkDayModelTest.php`
- **Metode Uji:**
  1. `test_work_day_status_helpers_return_correct_boolean()`:
     - Menguji `isFinal()` dan `needsReconciliation()` pada berbagai status.
  2. `test_scope_unfinalized_filters_only_pending_work_days()`:
     - Membuat 1 hari kerja final dan 2 hari kerja pending, memastikan query `unfinalized()` mengembalikan tepat 2 record.
  3. `test_work_date_casts_to_carbon_date_instance()`:
     - Memastikan `$workDay->work_date` adalah instance dari `\Illuminate\Support\Carbon`.
