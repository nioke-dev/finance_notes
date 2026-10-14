# TASK-043: Migrasi Skema Tabel Jadwal Cicilan Bulanan (`debt_installments`) & Model

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** Skema Tabel Angsuran Bulanan, Tanggal Jatuh Tempo, & Status Bayar  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `debt_installments` dan Model Eloquent `DebtInstallment`.
Sesuai rancangan data di `PRD.md` dan `DATA-MODEL.md`:
1. Terhubung secara relasional ke tabel `debts` (`debt_id`) dengan aturan `ON DELETE CASCADE`.
2. Menyimpan rincian tiap periode angsuran:
   - `installment_number`: Angsuran ke-1, ke-2, ke-3, dst.
   - `due_date`: Tanggal jatuh tempo angsuran per bulan (contoh: 5 November 2026).
   - `amount_due`: Nominal kewajiban yang harus dibayar pada periode tersebut (Rupiah utuh).
   - `amount_paid`: Nominal riil yang telah dibayarkan (default 0).
   - `status`: Enum string `unpaid` atau `paid`.
   - `paid_at`: Timestamp waktu pembayaran aktual.
3. Model memiliki helper method `isPaid(): bool` dan query scope `scopeUnpaid(Builder $query)`.

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000010_create_debt_installments_table.php` — File migrasi tabel.
2. `app/Models/DebtInstallment.php` — Model Eloquent.
3. `tests/Unit/Models/DebtInstallmentModelTest.php` — Automated test verifikasi skema dan model.

---

## 3. Spesifikasi Skema Database (`debt_installments`)
```php
Schema::create('debt_installments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('debt_id')->constrained('debts')->onDelete('cascade');
    $table->unsignedInteger('installment_number'); // Cicilan ke-1, 2, dst
    $table->date('due_date'); // Tanggal jatuh tempo
    $table->bigInteger('amount_due'); // Tagihan periode ini (Rupiah)
    $table->bigInteger('amount_paid')->default(0); // Riil dibayar
    $table->string('status', 20)->default('unpaid'); // unpaid, paid
    $table->timestamp('paid_at')->nullable(); // Waktu lunas
    $table->timestamps();

    // Indexes
    $table->index(['debt_id', 'installment_number']);
    $table->index(['status', 'due_date']);
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `debt_installments`.
2. Foreign key `debt_id` cascade delete jika data utang induk dihapus.
3. Model memiliki query scope `scopeUnpaid(Builder $query)`.
4. Kolom `amount_due` dan `amount_paid` bertipe `BIGINT` presisi Rupiah.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/DebtInstallmentModelTest.php`
- **Metode Uji:**
  1. `test_debt_installments_table_has_all_required_columns()`:
     - Memverifikasi kolom `id`, `debt_id`, `installment_number`, `due_date`, `amount_due`, `amount_paid`, `status`, `paid_at`.
  2. `test_installment_belongs_to_debt()`:
     - Memastikan relasi `$installment->debt` mengembalikan instance `Debt`.
  3. `test_scope_unpaid_filters_only_unpaid_installments()`:
     - Memverifikasi query `DebtInstallment::unpaid()->get()`.
