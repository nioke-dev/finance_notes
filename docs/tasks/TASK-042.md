# TASK-042: Migrasi Skema Tabel Master Utang & PayLater (`debts`) & Model

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Skema Tabel Induk Kewajiban Finansial, PayLater, & Talangan Teman  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `debts` dan Model Eloquent `Debt`.
Sesuai dokumen acuan di `PRD.md` dan `BUSINESS-RULES.md`:
1. Platform utang yang didukung:
   - `spaylater`: SPayLater (Shopee).
   - `seabank_pinjam`: SeaBank Pinjam.
   - `gopay_pinjam`: GoPay Pinjam.
   - `person`: Pinjaman ke/dari perorangan/teman (Rofik, Arno, Rafsan).
   - `other`: Platform/lembaga lain.
2. Memisahkan secara presisi:
   - `principal_amount`: Pokok pinjaman / harga barang.
   - `fee_amount`: Bunga / admin / biaya provisi.
   - `total_obligation`: Total kewajiban ($\text{Pokok} + \text{Biaya}$).
3. Tenor pinjaman (`tenor_months`) dan status (`active` / `paid_off`).

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000009_create_debts_table.php` — File migrasi tabel.
2. `app/Models/Debt.php` — Model Eloquent `Debt`.
3. `tests/Unit/Models/DebtModelTest.php` — Automated test verifikasi skema dan model.

---

## 3. Spesifikasi Skema Database (`debts`)
```php
Schema::create('debts', function (Blueprint $table) {
    $table->id();
    $table->string('name', 120); // Contoh: "SPayLater - Beli HP Rofik", "SeaBank Pinjam 3x"
    $table->string('platform', 30); // spaylater, seabank_pinjam, gopay_pinjam, person, other
    $table->string('borrower_name', 100)->nullable(); // Jika talangan teman, contoh: "Rofik"
    $table->bigInteger('principal_amount'); // Pokok pinjaman (Rupiah utuh)
    $table->bigInteger('fee_amount')->default(0); // Bunga / biaya pinjaman
    $table->bigInteger('total_obligation'); // Pokok + Biaya
    $table->unsignedInteger('tenor_months')->default(1); // Jumlah bulan cicilan
    $table->string('status', 20)->default('active'); // active, paid_off
    $table->date('started_at'); // Tanggal mulai transaksi pinjaman
    $table->timestamps();

    // Indexes
    $table->index('platform');
    $table->index('status');
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `debts`.
2. Seluruh kolom uang (`principal_amount`, `fee_amount`, `total_obligation`) bertipe `BIGINT` presisi Rupiah.
3. Model memiliki helper `isPaidOff(): bool` dan query scope `scopeActive(Builder $query)`.
4. Rollback berhasil menghapus tabel secara bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/DebtModelTest.php`
- **Metode Uji:**
  1. `test_debts_table_has_all_required_columns()`:
     - Memverifikasi kolom `id`, `name`, `platform`, `borrower_name`, `principal_amount`, `fee_amount`, `total_obligation`, `tenor_months`, `status`, `started_at`.
  2. `test_debt_status_helpers_and_scopes()`:
     - Menguji `isPaidOff()` bernilai `false` untuk utang `active` dan `true` untuk `paid_off`.
