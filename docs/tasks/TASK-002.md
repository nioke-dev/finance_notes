# TASK-002: Pembuatan PHP Enums Baku (`AccountType` & `EntryType`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Type Safety & Domain Enums Baku untuk Keuangan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat Enum PHP 8.2+ yang bertipe string (*backed enums*) untuk menjamin keamanan tipe (*type safety*) di seluruh sistem sebelum tabel database dibuat. Dilarang keras menggunakan string liar seperti `'bank'`, `'cash'`, atau `'debit'` secara manual di dalam query atau controller tanpa melalui Enum baku.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Enums/AccountType.php` — Enum jenis akun keuangan.
2. `app/Enums/EntryType.php` — Enum sisi buku ledger (Debit / Credit).
3. `tests/Unit/Enums/FinancialEnumsTest.php` — Unit test verifikasi nilai enum dan helper method.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Enums/AccountType.php`
```php
namespace App\Enums;

enum AccountType: string
{
    case BANK = 'bank';
    case EWALLET = 'ewallet';
    case CASH = 'cash';
    case INCOME = 'income';
    case EXPENSE = 'expense';
    case DEBT = 'debt';
    case RECEIVABLE = 'receivable';

    public function isAsset(): bool
    {
        return in_array($this, [self::BANK, self::EWALLET, self::CASH, self::RECEIVABLE]);
    }

    public function isDigital(): bool
    {
        return in_array($this, [self::BANK, self::EWALLET]);
    }

    public function isPhysical(): bool
    {
        return $this === self::CASH;
    }
}
```

### B. `app/Enums/EntryType.php`
```php
namespace App\Enums;

enum EntryType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function isDebit(): bool
    {
        return $this === self::DEBIT;
    }

    public function isCredit(): bool
    {
        return $this === self::CREDIT;
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. File `app/Enums/AccountType.php` dan `app/Enums/EntryType.php` terpasang dengan namespace baku `App\Enums`.
2. Seluruh case backed enum memiliki nilai string huruf kecil (`lowercase`).
3. Helper method `isAsset()`, `isDigital()`, dan `isPhysical()` mengembalikan boolean yang benar.
4. Unit test lulus 100% tanpa error.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Enums/FinancialEnumsTest.php`
- **Metode Uji:**
  1. `test_account_type_enum_has_all_required_cases()`:
     - Memverifikasi case `BANK`, `EWALLET`, `CASH`, `INCOME`, `EXPENSE`, `DEBT`, `RECEIVABLE`.
  2. `test_account_type_helpers_identify_asset_digital_and_physical()`:
     - Memastikan `AccountType::BANK->isDigital()` bernilai `true`.
     - Memastikan `AccountType::CASH->isPhysical()` bernilai `true`.
     - Memastikan `AccountType::CASH->isDigital()` bernilai `false`.
  3. `test_entry_type_enum_cases_and_helpers()`:
     - Memastikan hanya ada case `DEBIT` dan `CREDIT`.
