# LOG KEPUTUSAN ARSITEKTUR & PRODUK (DECISIONS.md)
**Status:** DOKUMEN KONTROL KEPUTUSAN  

Setiap kali ada keputusan penting atau perubahan arah fitur dari pengguna, keputusan tersebut dicatat di sini beserta alasan dan konsekuensinya terhadap kode/task.

---

## Format Pencatatan Keputusan (ADR - Architecture Decision Record)
Setiap keputusan dicatat dengan struktur:
- **ID:** `ADR-XXX`
- **Tanggal:** Tanggal kesepakatan
- **Judul Keputusan:** Ringkasan singkat
- **Status:** `DIKUNCI` / `DIREVISI` / `DIBATALKAN`
- **Konteks & Masalah:** Latar belakang masalah di lapangan
- **Keputusan Diambil:** Solusi yang dipilih oleh pengguna
- **Dampak Dokumen & Task:** Daftar dokumen/task yang terpengaruh

---

## Riwayat Keputusan

### ADR-001: Pengakuan Bonus Hub Jam 20.00 & Pencairan ShopeePay Jam 22.00
- **Tanggal:** 14 Oktober 2026
- **Status:** `DIKUNCI`
- **Konteks:** Perhitungan bonus hub membutuhkan waktu online $\ge$ 9 jam. Cut-off order adalah jam 20.00, tetapi pencairan uang oleh Shopee baru jam 22.00–22.30.
- **Keputusan:** Pada jam 20.01 pengguna mengonfirmasi waktu online $\ge$ 9 jam. Jika lolos, bonus diakui di estimasi pendapatan hari itu. Saat closing malam, pengguna mengonfirmasi pencairan saldo ShopeePay.
- **Dampak:** `BUSINESS-RULES.md`, `PRD.md`, `TASK-009`, `TASK-011`.

### ADR-002: Penanganan Selisih Rekonsiliasi Tanpa Status Rumit
- **Tanggal:** 14 Oktober 2026
- **Status:** `DIKUNCI`
- **Konteks:** Pengguna lelah saat closing malam dan sering ada selisih yang belum ketemu penyebabnya. Pengguna menolak status akuntansi yang membingungkan.
- **Keputusan:** Tanggal tetap berstatus `needs_reconciliation`. Pengguna bebas membuka tanggal baru keesokan harinya. Aplikasi mobile menampilkan banner pengingat mencolok. Tanggal hanya bisa `final` jika selisih = Rp0.
- **Dampak:** `BUSINESS-RULES.md`, `PRD.md`, `TASK-019`, `TASK-020`.

### ADR-003: Quick Actions & Voice Memo Intercom Helm
- **Tanggal:** 14 Oktober 2026
- **Status:** `DIKUNCI`
- **Konteks:** Di jalan (on-beat) mustahil mengetik form panjang untuk parkir atau mencatat COD yang rumit.
- **Keputusan:** Disediakan tombol 1-tap Quick Parkir (GPS), 1-tap Bensin (SPBU/Madura), dan rekaman audio Voice Memo via intercom helm untuk diputar ulang saat closing malam.
- **Dampak:** `PRD.md`, `DATA-MODEL.md`, `TASK-014`, `TASK-015`, `TASK-016`.

### ADR-004: Pelunasan Utang Teman dengan Opsi Auto-Sedekah
- **Tanggal:** 14 Oktober 2026
- **Status:** `DIKUNCI`
- **Konteks:** Pembayaran utang teman yang kurang (misal kurang Rp1.000) sering diikhlaskan.
- **Keputusan:** Disediakan form 1-tap dengan opsi `diikhlaskan`. Sisa utang otomatis dipindahkan ke akun beban Sedekah/Bantuan Pribadi, dan status utang ditutup lunas.
- **Dampak:** `BUSINESS-RULES.md`, `DATA-MODEL.md`, `TASK-017`.
