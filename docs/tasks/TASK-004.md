# TASK-004: Model Eloquent `Account` (Casting Enums & Scopes Keuangan)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Model Domain `Account` dengan Enums Casting & Query Scopes  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun Model Eloquent `Account` yang menghubungkan tabel `accounts` ke aplikasi Laravel. Model ini wajib:
1. Melakukan auto-casting kolom `type` ke `AccountType` backed enum.
2. Melakukan casting kolom saldo (`current_balance`, `initial_balance`) sebagai `integer`.
3. Menyediakan query scope untuk menyaring akun:
   - `scopeActive()`: Hanya akun aktif.
   - `scopeDigital()`: Akun Bank & E-Wallet (BCA, SeaBank, ShopeePay, GoPay, GoPay Merchant).
   - `scopePhysical()`: Akun Cash/Tunai.
   - `scopeAssets()`: Seluruh aset lancar milik driver.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Models/Account.php` — Model Eloquent.
2. `tests/Unit/Models/AccountModelTest.php` — Automated test verifikasi perilaku model.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/Account.php`)
```php
namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'current_balance',
        'initial_balance',
        'is_active',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'current_balance' => 'integer',
        'initial_balance' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDigital(Builder $query): Builder
    {
        return $query->whereIn('type', [AccountType::BANK->value, AccountType::EWALLET->value]);
    }

    public function scopePhysical(Builder $query): Builder
    {
        return $query->where('type', AccountType::CASH->value);
    }

    public function scopeAssets(Builder $query): Builder
    {
        return $query->whereIn('type', [
            AccountType::BANK->value,
            AccountType::EWALLET->value,
            AccountType::CASH->value,
            AccountType::RECEIVABLE->value,
        ]);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Model `Account` tersimpan di namespace `App\Models`.
2. Property `$casts` memetakan kolom `type` ke `AccountType::class` secara otomatis.
3. Seluruh scope (`active`, `digital`, `physical`, `assets`) mengembalikan instance Builder yang valid.
4. Unit tests lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/AccountModelTest.php`
- **Metode Uji:**
  1. `test_account_casts_type_to_enum_instance()`:
     - Menguji apakah `$account->type` adalah instance dari `AccountType`.
  2. `test_account_balances_are_strictly_cast_to_integer()`:
     - Menguji nilai saldo tetap berupa integer murni.
  3. `test_scopes_filter_accounts_correctly()`:
     - Membuat data dummy akun bank, cash, dan expense, lalu memverifikasi hasil filter `Account::digital()->get()` dan `Account::physical()->get()`.
