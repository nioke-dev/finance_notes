# TASK-009: Migrasi Skema Tabel Tanggal Kerja Driver (`work_days`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Skema Tabel Tanggal Kerja Terpisah dari Waktu Sistem  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `work_days`. Sesuai aturan bisnis permanen di `BUSINESS-RULES.md`:
- **Tanggal Kerja (`work_date`) terpisah dari waktu sistem/closing.** Driver yang menyelesaikan kerja tanggal 12 Oktober pada pukul 03.00 subuh tanggal 13 Oktober tetap mencatat transaksi di bawah tanggal kerja 12 Oktober.
- **Status Closing Baku:**
  - `needs_reconciliation`: Status default saat tanggal kerja dibuka atau jika ada selisih uang yang belum klop.
  - `final`: Status terkunci permanen setelah seluruh saldo buku dan fisik cocok 100% (selisih Rp0).
- Kolom `work_date` wajib unik (satu hari kalender hanya memiliki satu entitas tanggal kerja).

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000003_create_work_days_table.php` — File migrasi tabel.
2. `tests/Unit/Migrations/WorkDaysTableMigrationTest.php` — Automated test verifikasi skema tabel.

---

## 3. Spesifikasi Skema Database (`work_days`)
```php
Schema::create('work_days', function (Blueprint $table) {
    $table->id();
    $table->date('work_date')->unique(); // Contoh: '2026-10-14'
    $table->string('status', 30)->default('needs_reconciliation'); // needs_reconciliation, final
    $table->timestamp('opened_at')->useCurrent(); // Waktu driver mulai membuka hari kerja
    $table->timestamp('closed_at')->nullable(); // Waktu driver memfinalisasi hari kerja
    $table->unsignedInteger('total_orders')->default(0); // Akumulasi order selesai
    $table->decimal('online_hours', 4, 2)->nullable(); // Waktu online (misal: 9.25 jam)
    $table->boolean('is_performance_eligible')->default(false); // Apakah online >= 9 jam
    $table->text('closing_notes')->nullable(); // Catatan investigasi closing jika ada
    $table->timestamps();

    // Indexes
    $table->index('status');
    $table->index('work_date');
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `work_days`.
2. Kolom `work_date` memiliki indeks unik (mencegah duplikasi tanggal kerja yang sama).
3. Kolom `status` memiliki nilai bawaan `'needs_reconciliation'`.
4. Perintah rollback berhasil menghapus tabel secara bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Migrations/WorkDaysTableMigrationTest.php`
- **Metode Uji:**
  1. `test_work_days_table_has_all_required_columns()`:
     - Memverifikasi kolom `id`, `work_date`, `status`, `opened_at`, `closed_at`, `total_orders`, `online_hours`, `is_performance_eligible`, `closing_notes`.
  2. `test_work_date_must_be_unique()`:
     - Memastikan database menolak pembuatan dua row dengan `work_date` yang identik.
  3. `test_default_status_is_needs_reconciliation()`:
     - Memastikan status baru otomatis bernilai `'needs_reconciliation'`.
