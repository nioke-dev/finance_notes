# TASK-075: Model Eloquent `Category` & Seeder Kategori Bawaan Driver

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Model Eloquent Kategori & Default Seeder Pengeluaran/Pendapatan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Model Eloquent `Category` dan Seeder bawaan untuk kebutuhan operasional harian driver ShopeeFood Malang:
1. **Model `Category` (`app/Models/Category.php`):**
   - `$fillable`: `name`, `type`, `is_system`, `icon`, `color`.
   - Cast: `is_system => boolean`.
   - Local Scopes: `scopeExpense()`, `scopeIncome()`.
2. **Seeder Kategori Bawaan (`CategorySeeder`):**
   - **Expense:**
     - Bensin Motor (Icon: `fuel`, Color: `#F59E0B`, `is_system: true`)
     - Parkir Resto/Pelanggan (Icon: `parking`, Color: `#38BDF8`, `is_system: true`)
     - Makan & Minum di Jalan (Icon: `utensils`, Color: `#FB923C`, `is_system: true`)
     - Perawatan Kendaraan / Servis (Icon: `wrench`, Color: `#94A3B8`, `is_system: true`)
     - Cicilan PayLater (Icon: `credit-card`, Color: `#F43F5E`, `is_system: true`)
     - Sedekah / Bantuan Pribadi (Icon: `heart`, Color: `#EC4899`, `is_system: true`)
     - Biaya Admin Transfer (Icon: `receipt`, Color: `#64748B`, `is_system: true`)
   - **Income:**
     - Pendapatan Hub Shopee (Icon: `bike`, Color: `#EE4D2D`, `is_system: true`)
     - Bonus Hub Shopee (Icon: `award`, Color: `#FBBF24`, `is_system: true`)
     - Tip Pelanggan QRIS / Cash (Icon: `coins`, Color: `#10B981`, `is_system: true`)
     - Pelunasan Utang Teman (Icon: `user-check`, Color: `#34D399`, `is_system: true`)

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Models/Category.php` — Model Eloquent Category.
2. `database/seeders/CategorySeeder.php` — Seeder kategori bawaan.
3. `tests/Unit/Models/CategoryTest.php` — Unit test Model dan Seeder.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/Category.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    protected $fillable = ['name', 'type', 'is_system', 'icon', 'color'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Model `Category` dapat melakukan query dengan scope `Category::expense()->get()`.
2. Seeder `CategorySeeder` berhasil mengisi seluruh kategori bawaan sistem tanpa duplikasi (`firstOrCreate`).
3. Unit test memverifikasi integritas data seeder dan casting boolean.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/CategoryTest.php`
- **Metode Uji:**
  1. `test_category_seeder_populates_required_system_categories()`:
     - Jalankan `CategorySeeder`.
     - Verifikasi kategori 'Bensin Motor' dan 'Pendapatan Hub Shopee' ada di database dengan `is_system == true`.
  2. `test_category_scopes_filter_by_type()`:
     - Verifikasi `Category::expense()->count()` hanya mengembalikan kategori bertipe expense.
