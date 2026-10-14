# TASK-049: Migrasi Skema Tabel Hitung Fisik Denominasi (`cash_denominations`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** Skema Tabel Hitung Fisik Uang Tunai Closing Malam Lengkap Pecahan Koin & Kertas  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `cash_denominations`.
Sesuai rancangan audit fisik di `PRD.md` dan `BUSINESS-RULES.md`:
1. Setiap malam saat pulang ke kos, driver menghitung uang fisik yang ada di dompet dan kantong.
2. Driver memasukkan kuantitas per pecahan uang tunai yang beredar di Indonesia:
   - **Pecahan Koin**:
     - `coin_100` (Rp100)
     - `coin_200` (Rp200)
     - `coin_500` (Rp500)
     - `coin_1000` (Rp1.000)
   - **Pecahan Uang Kertas**:
     - `bill_1000` (Rp1.000)
     - `bill_2000` (Rp2.000)
     - `bill_5000` (Rp5.000)
     - `bill_10000` (Rp10.000)
     - `bill_20000` (Rp20.000)
     - `bill_50000` (Rp50.000)
     - `bill_100000` (Rp100.000)
3. Relasi unik 1-ke-1 (`UNIQUE`) dengan `work_days` (satu hari kerja hanya memiliki satu snapshot rekonsiliasi kas fisik closing).
4. Menyimpan total hasil hitung fisik di kolom `total_physical_amount` bertipe `BIGINT` presisi Rupiah.

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000011_create_cash_denominations_table.php` — File migrasi tabel.
2. `tests/Unit/Migrations/CashDenominationsTableMigrationTest.php` — Automated test verifikasi skema tabel.

---

## 3. Spesifikasi Skema Database (`cash_denominations`)
```php
Schema::create('cash_denominations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('work_day_id')->unique()->constrained('work_days')->onDelete('cascade');
    
    // Koin
    $table->unsignedInteger('coin_100')->default(0);
    $table->unsignedInteger('coin_200')->default(0);
    $table->unsignedInteger('coin_500')->default(0);
    $table->unsignedInteger('coin_1000')->default(0);

    // Kertas
    $table->unsignedInteger('bill_1000')->default(0);
    $table->unsignedInteger('bill_2000')->default(0);
    $table->unsignedInteger('bill_5000')->default(0);
    $table->unsignedInteger('bill_10000')->default(0);
    $table->unsignedInteger('bill_20000')->default(0);
    $table->unsignedInteger('bill_50000')->default(0);
    $table->unsignedInteger('bill_100000')->default(0);

    // Total Akumulasi
    $table->bigInteger('total_physical_amount')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('work_day_id');
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `cash_denominations`.
2. Kolom `work_day_id` bersifat `unique` (mencegah duplikasi closing fisik pada hari yang sama).
3. Kolom `total_physical_amount` bertipe `BIGINT` presisi integer Rupiah.
4. Rollback berhasil menghapus tabel secara bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Migrations/CashDenominationsTableMigrationTest.php`
- **Metode Uji:**
  1. `test_cash_denominations_table_has_all_currency_breakdown_columns()`:
     - Memverifikasi 4 pecahan koin dan 7 pecahan kertas ada di skema database.
  2. `test_work_day_id_is_strictly_unique()`:
     - Memastikan database menolak pembuatan 2 row denominasi untuk `work_day_id` yang sama.
