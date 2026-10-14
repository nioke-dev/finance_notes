# TASK-025: Migrasi Skema Tabel Snapshot Progres On-Beat (`driver_snapshots`)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** Skema Tabel Penyimpanan Riwayat Progres Driver Tanpa Menimpa Data Lama  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `driver_snapshots`.
Sesuai dokumen aturan bisnis `BUSINESS-RULES.md`:
1. Driver sering mengecek dan memasukkan progres beberapa kali dalam satu tanggal kerja (misalnya jam 15.00 sore, jam 20.00 malam, dan jam 23.00 malam).
2. Setiap kali driver menyimpan snapshot progres di jalan:
   - Sistem **DILARANG KERAS** menimpa (*overwrite*) data sebelumnya.
   - Wajib disimpan sebagai row baru dengan timestamp `recorded_at` untuk audit perkembangan ritme kerja driver.
3. Menampung 4 instrumen lengkap:
   - Driver ShopeeFood: `completed_orders`, `hub_income`, `hub_bonus`, `customer_tips`
   - Saldo dompet: `shopeepay_balance`, `gopay_balance`, `gopay_merchant_balance`
   - Seluruh nominal uang bertipe `BIGINT` presisi Rupiah utuh.

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000007_create_driver_snapshots_table.php` — File migrasi tabel.
2. `tests/Unit/Migrations/DriverSnapshotsTableMigrationTest.php` — Automated test verifikasi skema tabel.

---

## 3. Spesifikasi Skema Database (`driver_snapshots`)
```php
Schema::create('driver_snapshots', function (Blueprint $table) {
    $table->id();
    $table->foreignId('work_day_id')->constrained('work_days')->onDelete('cascade');
    $table->timestamp('recorded_at'); // Waktu snapshot disimpan
    $table->unsignedInteger('completed_orders')->default(0); // Jumlah pesanan selesai
    $table->bigInteger('hub_income')->default(0); // Pendapatan Hub (Rupiah)
    $table->bigInteger('hub_bonus')->default(0); // Estimasi / Nilai Bonus Hub (Rupiah)
    $table->bigInteger('customer_tips')->default(0); // Tip pelanggan di Shopee (Rupiah)
    $table->bigInteger('shopeepay_balance')->default(0); // Saldo rekening ShopeePay saat itu
    $table->bigInteger('gopay_balance')->default(0); // Saldo rekening GoPay saat itu
    $table->bigInteger('gopay_merchant_balance')->default(0); // Saldo QRIS GoPay Merchant saat itu
    $table->timestamps();

    // Indexes
    $table->index(['work_day_id', 'recorded_at']);
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `driver_snapshots`.
2. Seluruh kolom uang bertipe bigint/integer tanpa desimal koma.
3. Foreign key `work_day_id` cascade delete jika hari kerja dibersihkan.
4. Rollback sukses menghapus tabel secara bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Migrations/DriverSnapshotsTableMigrationTest.php`
- **Metode Uji:**
  1. `test_driver_snapshots_table_has_all_required_columns()`:
     - Memverifikasi kolom `id`, `work_day_id`, `recorded_at`, `completed_orders`, `hub_income`, `hub_bonus`, `customer_tips`, `shopeepay_balance`, `gopay_balance`, `gopay_merchant_balance`.
  2. `test_multiple_snapshots_can_be_created_for_same_work_day()`:
     - Memastikan database mendukung multi-snapshot untuk `work_day_id` yang sama.
