# TASK-012: Migrasi Skema Tabel Split Mutasi Multi-Entry (`ledger_entries`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Skema Tabel Split Mutasi Double-Entry Presisi Rp1  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `ledger_entries`. Tabel ini adalah fondasi matematis utama seluruh sistem keuangan:
- Setiap 1 transaksi induk dipecah menjadi minimal 2 baris ledger (Debit dan Kredit).
- Terhubung secara ketat ke `transactions` dan `accounts` dengan aturan `ON DELETE RESTRICT` (dilarang keras menghapus akun atau transaksi yang sudah memiliki catatan mutasi).
- Kolom `amount` bertipe `BIGINT` tanpa koma (satuan Rupiah utuh, presisi Rp1, selalu $\ge 0$).
- Kolom `entry_type` menampung nilai dari enum `EntryType` (`debit` / `credit`).
- Tidak memiliki kolom `updated_at` (mutasi ledger bersifat kekal / *immutable*).

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000005_create_ledger_entries_table.php` — File migrasi tabel.
2. `tests/Unit/Migrations/LedgerEntriesTableMigrationTest.php` — Automated test verifikasi skema tabel.

---

## 3. Spesifikasi Skema Database (`ledger_entries`)
```php
Schema::create('ledger_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('transaction_id')->constrained('transactions')->onDelete('restrict');
    $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
    $table->string('entry_type', 15); // debit, credit (dari EntryType enum)
    $table->bigInteger('amount'); // Selalu positif (>= 0), presisi Rp1
    $table->timestamp('created_at')->useCurrent();

    // Indexes
    $table->index(['account_id', 'created_at']);
    $table->index(['transaction_id', 'entry_type']);
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `ledger_entries`.
2. Foreign key `transaction_id` dan `account_id` terpasang dengan `ON DELETE RESTRICT`.
3. Kolom `amount` bertipe integer/bigint (bukan float atau double).
4. Tabel tidak memiliki kolom `updated_at`.
5. Rollback berhasil menghapus tabel secara bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Migrations/LedgerEntriesTableMigrationTest.php`
- **Metode Uji:**
  1. `test_ledger_entries_table_has_all_required_columns()`:
     - Memverifikasi kolom `id`, `transaction_id`, `account_id`, `entry_type`, `amount`, `created_at`.
  2. `test_cannot_delete_account_that_has_ledger_entries()`:
     - Memastikan database menolak penghapusan akun yang memiliki riwayat mutasi ledger.
  3. `test_ledger_entries_amount_is_bigint_without_decimals()`:
     - Memastikan nominal tersimpan sebagai integer murni.
