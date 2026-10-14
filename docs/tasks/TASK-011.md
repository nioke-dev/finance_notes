# TASK-011: Migrasi Skema Tabel Header Transaksi (`transactions`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Skema Tabel Induk Transaksi Keuangan dengan Metadata Fleksibel  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `transactions`. Tabel ini bertindak sebagai payung induk (*parent transaction*) untuk setiap aktivitas keuangan, baik pemasukan driver, pengeluaran bensin/parkir, transfer antar-rekening, maupun pelunasan utang.
- Terhubung secara relasional ke `work_days` (setiap transaksi wajib dimiliki oleh satu hari kerja).
- Memisahkan waktu kejadian aktual (`transaction_time`) dari waktu input sistem (`created_at`).
- Mendukung flag `is_estimated_time` untuk menandai transaksi terlupa yang diinput menggunakan placeholder waktu 23:59.
- Kolom `metadata` bertipe JSON untuk menyimpan data fleksibel di jalan (koordinat GPS parkir, nama resto, nomor order, tipe vendor bensin SPBU/Madura).

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000004_create_transactions_table.php` — File migrasi tabel `transactions`.
2. `tests/Unit/Migrations/TransactionsTableMigrationTest.php` — Automated test verifikasi skema tabel.

---

## 3. Spesifikasi Skema Database (`transactions`)
```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('work_day_id')->constrained('work_days')->onDelete('restrict');
    $table->string('type', 30); // income, expense, transfer, adjustment, reversal
    $table->string('description', 255); // Keterangan transaksi
    $table->timestamp('transaction_time'); // Waktu kejadian aktual
    $table->boolean('is_estimated_time')->default(false); // True jika placeholder 23:59
    $table->json('metadata')->nullable(); // GPS, resto, volume liter, dll
    $table->timestamps();

    // Indexes
    $table->index('type');
    $table->index('transaction_time');
    $table->index(['work_day_id', 'transaction_time']);
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `transactions`.
2. Foreign key `work_day_id` terpasang dengan proteksi `ON DELETE RESTRICT` (mencegah penghapusan hari kerja yang memiliki riwayat transaksi).
3. Kolom `metadata` bertipe JSON nullable.
4. Perintah rollback berhasil menghapus tabel secara bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Migrations/TransactionsTableMigrationTest.php`
- **Metode Uji:**
  1. `test_transactions_table_has_all_required_columns()`:
     - Memverifikasi kolom `id`, `work_day_id`, `type`, `description`, `transaction_time`, `is_estimated_time`, `metadata`.
  2. `test_cannot_delete_work_day_with_associated_transactions()`:
     - Memastikan foreign key constraint mencegah penghapusan `work_days` secara tidak sengaja.
  3. `test_metadata_column_can_store_and_retrieve_json()`:
     - Menyimpan payload JSON GPS/resto ke `metadata` dan memverifikasi integritasnya.
