# PANDUAN KERJA AGENT (AGENTS.md)

Dokumen ini adalah instruksi permanen untuk AI Coding Agent (Google Anti-Gravity) dalam mengembangkan aplikasi **Keuangan Pribadi & ShopeeFood Driver**.

---

## 1. Peran & Tanggung Jawab
- **Google Anti-Gravity (Coding Agent)**:
  - Mengimplementasikan kode backend (Laravel) dan mobile (Flutter) sesuai spesifikasi yang ada di folder `docs/`.
  - Mengerjakan task secara bertahap, **satu per satu** sesuai urutan di `docs/TASK-BACKLOG.md` dan file task terkait di `docs/tasks/`.
  - **Wajib menjalankan automated tests** untuk setiap perubahan logika bisnis sebelum menandai task selesai.
  - Tidak membuat asumsi baru di luar aturan bisnis yang sudah dikunci di `docs/BUSINESS-RULES.md`.

---

## 2. Prinsip Rekayasa & Standar Kualitas
1. **Presisi Finansial**:
   - Semua perhitungan uang menggunakan integer (Rupiah utuh, presisi Rp1). Dilarang ada pembulatan tersembunyi.
2. **Audit Trail & Immutability**:
   - Dilarang keras melakukan hard delete atau update diam-diam pada data transaksi keuangan.
   - Setiap koreksi wajib menyimpan data histori, alasan koreksi, dan timestamp.
3. **Automated Testing Diutamakan**:
   - Pengguna tidak boleh dibebani testing manual berulang.
   - Setiap endpoint, service logika, rekonsiliasi, dan kalkulasi bonus wajib memiliki Unit / Feature Test otomatis (Pest/PHPUnit di Laravel).
4. **UI Konsisten & Reusable**:
   - Dilarang meninggalkan form mentah tanpa styling bawaan browser.
   - Gunakan komponen blade/tailwind atau Flutter widget yang rapi, responsif, dan konsisten.

---

## 3. Workflow Eksekusi Task
1. Buka `docs/TASK-BACKLOG.md` dan pilih task aktif berikutnya yang berstatus `[READY]`.
2. Baca file rincian task di `docs/tasks/TASK-XXX.md`.
3. Tulis kode implementasi dan automated test terkait.
4. Jalankan test suite lokal untuk memastikan 100% test lulus.
5. Perbarui status task di `docs/TASK-BACKLOG.md` menjadi `[DONE]` dan laporkan ringkasan hasil test secara singkat.
