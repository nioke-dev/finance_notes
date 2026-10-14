# TASK-035: Migrasi Skema Tabel Voice Memo Intercom Helm (`voice_memos`) & Storage Audio

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** Skema Tabel Audio Memo Rekaman Suara Intercom Helm & Konfigurasi Storage  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file migrasi database Laravel tunggal untuk tabel `voice_memos` dan memastikan direktori storage penyimpanan file audio siap digunakan.
Sesuai kebutuhan pengguna di `PRD.md` dan `BUSINESS-RULES.md`:
1. Driver menggunakan headset intercom di helm untuk merekam memo suara pendek saat motoran di jalan (menangkap konteks COD pelanggan, tip berlebih, atau catatan celah parkir gratis dekat Indomaret tanpa mengetik).
2. Memo suara ini disimpan secara terorganisir per hari kerja (`work_day_id`).
3. Menyimpan:
   - `file_path`: Lokasi file audio di storage disk lokal (`voice_memos/memo_xxx.m4a`).
   - `duration_seconds`: Durasi rekaman audio dalam detik.
   - `latitude` & `longitude`: Titik koordinat GPS saat driver menekan tombol rekam di jalan.
   - `transcription_text`: Nullable (kosong di MVP, disiapkan untuk AI speech-to-text masa depan).
   - `recorded_at`: Timestamp waktu kejadian riil saat suara direkam.

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000008_create_voice_memos_table.php` — File migrasi tabel.
2. `tests/Unit/Migrations/VoiceMemosTableMigrationTest.php` — Automated test verifikasi skema tabel.

---

## 3. Spesifikasi Skema Database (`voice_memos`)
```php
Schema::create('voice_memos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('work_day_id')->constrained('work_days')->onDelete('cascade');
    $table->string('file_path', 255); // Path file audio di storage
    $table->unsignedInteger('duration_seconds')->nullable(); // Durasi rekaman audio
    $table->decimal('latitude', 10, 8)->nullable(); // Koordinat GPS latitude
    $table->decimal('longitude', 11, 8)->nullable(); // Koordinat GPS longitude
    $table->text('transcription_text')->nullable(); // Disiapkan untuk masa depan
    $table->timestamp('recorded_at'); // Waktu rekaman suara diambil
    $table->timestamps();

    // Indexes
    $table->index(['work_day_id', 'recorded_at']);
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `voice_memos`.
2. Foreign key `work_day_id` cascade delete jika hari kerja dibersihkan di masa depan.
3. Kolom `latitude` dan `longitude` bertipe decimal presisi koordinat GPS.
4. Perintah rollback berhasil menghapus tabel secara bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Migrations/VoiceMemosTableMigrationTest.php`
- **Metode Uji:**
  1. `test_voice_memos_table_has_all_required_columns()`:
     - Memverifikasi kolom `id`, `work_day_id`, `file_path`, `duration_seconds`, `latitude`, `longitude`, `transcription_text`, `recorded_at`.
  2. `test_latitude_and_longitude_precision()`:
     - Memastikan kolom koordinat mampu menyimpan desimal presisi tinggi hingga 8 angka di belakang koma.
