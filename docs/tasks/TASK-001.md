# TASK-001: Inisialisasi Project Laravel 11, Pembersihan Boilerplate, & Setup In-Memory SQLite Testing

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Setup kerangka kerja Laravel 11 & Test Engine in-memory  
**Status:** `[READY]`  
**Estimasi Waktu Pengerjaan Agent:** 1–2 Menit  

---

## 1. Deskripsi Detail Teknis
Menginisialisasi kerangka kerja Laravel 11 di direktori root atau direktori backend proyek. Melakukan pembersihan boilerplate yang tidak diperlukan (seperti view bawaan yang tidak terpakai), serta mengonfigurasi environment pengujian otomatis (`phpunit.xml`) agar mutlak menggunakan in-memory SQLite (`:memory:`) dan koneksi terisolasi untuk testing, sehingga setiap test suite yang dijalankan berjalan instan dalam hitungan milidetik tanpa merusak database development.

---

## 2. File-File yang Disentuh / Dibuat
1. `composer.json` — Konfigurasi dependensi Laravel 11.
2. `phpunit.xml` — Konfigurasi test environment (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
3. `.env.example` & `.env` — Setup variabel dasar aplikasi (`APP_NAME`, `APP_ENV=local`).
4. `tests/TestCase.php` — Base test class untuk Laravel.
5. `tests/Feature/SanityTest.php` — Uji verifikasi sanity check lingkungan aplikasi.

---

## 3. Spesifikasi Teknis Konfigurasi `phpunit.xml`
Pada bagian `<php>`, wajib mengunci environment testing berikut:
```xml
<env name="APP_ENV" value="testing"/>
<env name="BCRYPT_ROUNDS" value="4"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="MAIL_MAILER" value="array"/>
<env name="PULSE_ENABLED" value="false"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="TELESCOPE_ENABLED" value="false"/>
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Project Laravel 11 terpasang dengan struktur standar (`app/`, `bootstrap/`, `config/`, `routes/`, `tests/`).
2. Perintah `php artisan --version` mengembalikan versi Laravel 11.x.
3. Konfigurasi `phpunit.xml` telah diarahkan ke SQLite `:memory:`.
4. Menjalankan perintah `php artisan test` mengeksekusi test suite dan menghasilkan status `PASS` (hijau) 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/SanityTest.php`
- **Metode Uji:**
  1. `test_application_environment_is_set_to_testing()`:
     - Memastikan `app()->environment()` bernilai persis `'testing'`.
  2. `test_database_connection_uses_sqlite_in_memory()`:
     - Memastikan koneksi database aktif adalah driver `'sqlite'`.
     - Memastikan database name adalah `':memory:'`.
