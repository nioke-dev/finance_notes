# TASK-003: Migrasi Skema Tabel Master Akun Keuangan (`accounts`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Database Migration Tabel `accounts` dengan Presisi Integer Rupiah  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `accounts`. Tabel ini menampung seluruh akun keuangan pengguna (BCA, ShopeePay, GoPay, GoPay Merchant, Cash, SeaBank, dsb) serta pos beban dan pendapatan.
- **Prinsip Utama:** Kolom saldo (`current_balance` dan `initial_balance`) wajib menggunakan tipe `BIGINT` untuk presisi Rp1 tanpa toleransi desimal floating-point.
- Menggunakan Enum `AccountType` yang sudah dibuat pada `TASK-002`.

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000001_create_accounts_table.php` — File migrasi skema tabel.
2. `tests/Unit/Migrations/AccountsTableMigrationTest.php` — Automated test verifikasi skema tabel.

---

## 3. Spesifikasi Skema Database
```php
Schema::create('accounts', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100); // Contoh: "BCA", "ShopeePay", "Cash", "Beban Parkir"
    $table->string('type', 30); // Nilai dari AccountType enum
    $table->bigInteger('current_balance')->default(0); // Satuan Rupiah penuh (presisi Rp1)
    $table->bigInteger('initial_balance')->default(0); // Saldo modal awal
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    // Indexes
    $table->index('type');
    $table->index('is_active');
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` berjalan sukses tanpa error.
2. Kolom `current_balance` dan `initial_balance` bertipe integer/bigint (bukan float, double, atau decimal).
3. Tabel memiliki indeks pada kolom `type` dan `is_active`.
4. Perintah `php artisan migrate:rollback` sukses menghapus tabel dengan bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Migrations/AccountsTableMigrationTest.php`
- **Metode Uji:**
  1. `test_accounts_table_has_expected_columns()`:
     - Memverifikasi keberadaan kolom `id`, `name`, `type`, `current_balance`, `initial_balance`, `is_active`, `created_at`, `updated_at`.
  2. `test_current_balance_column_defaults_to_zero()`:
     - Memastikan akun yang baru dibuat tanpa saldo memiliki default `0`.
  3. `test_accounts_table_can_be_rolled_back()`:
     - Memastikan rollback menghapus tabel tanpa error.
