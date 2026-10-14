# TASK-006: Migrasi Tabel `audit_logs` & Model `AuditLog`

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Infrastruktur Audit Trail & Immutability Keuangan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun tabel database `audit_logs` dan Model Eloquent `AuditLog`. Sesuai standar `AGENTS.md`:
- Sistem **DILARANG KERAS** melakukan hard delete atau mengubah data keuangan secara diam-diam.
- Setiap kali ada koreksi saldo awal, pembalikan transaksi, penambahan transaksi terlambat, atau perubahan konfigurasi, sistem wajib mencatat:
  - Model apa yang disentuh (`auditable_type` & `auditable_id`)
  - Aksi yang dilakukan (`create`, `update`, `reversal`)
  - Nilai sebelum diubah (`old_values` dalam format JSON)
  - Nilai sesudah diubah (`new_values` dalam format JSON)
  - Alasan perubahan (`reason` teks wajib diisi)
  - Waktu pencatatan (`created_at`)

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000002_create_audit_logs_table.php` — File migrasi tabel `audit_logs`.
2. `app/Models/AuditLog.php` — Model Eloquent `AuditLog`.
3. `tests/Unit/Models/AuditLogModelTest.php` — Automated test verifikasi integritas audit log.

---

## 3. Spesifikasi Skema Database (`audit_logs`)
```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('auditable_type', 120); // Contoh: App\Models\Account
    $table->unsignedBigInteger('auditable_id');
    $table->string('action', 30); // create, update, reversal, adjustment
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->text('reason'); // Alasan koreksi wajib diisi
    $table->timestamp('created_at')->useCurrent();

    // Indexes
    $table->index(['auditable_type', 'auditable_id']);
    $table->index('action');
    $table->index('created_at');
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Migrasi `audit_logs` berjalan sukses tanpa error.
2. Kolom `reason` bersifat wajib (`NOT NULL`).
3. Model `AuditLog` memiliki auto-casting:
   - `old_values` $\rightarrow$ `array`
   - `new_values` $\rightarrow$ `array`
   - `created_at` $\rightarrow$ `datetime`
4. Model tidak mengizinkan operasi `updated_at` (hanya `created_at` / *append-only log*).

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/AuditLogModelTest.php`
- **Metode Uji:**
  1. `test_audit_log_persists_old_and_new_json_payloads_as_arrays()`:
     - Menyimpan array PHP ke `old_values` dan `new_values`, lalu memastikan saat di-retrieve kembali dalam bentuk array utuh.
  2. `test_audit_log_requires_reason_to_be_present()`:
     - Memastikan pembuatan audit log tanpa `reason` melempar database exception.
  3. `test_audit_log_is_append_only_without_updated_at_column()`:
     - Memverifikasi kolom `updated_at` tidak ada di tabel `audit_logs`.
