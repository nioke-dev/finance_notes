# PANDUAN PENGUJIAN LENGKAP END-TO-END (UAT)
## SUPER APP KEUANGAN PRIBADI & SHOPEEFOOD DRIVER MALANG
**Status Database**: Telah di-reset bersih ke kondisi awal (Seeded Clean State Rp0)  
**Target Server**: [https://financenotes.nurulmustofa.my.id](https://financenotes.nurulmustofa.my.id)

---

## 📌 KREDENSIAL LOGIN PENGUJIAN
Gunakan akun default berikut yang telah disediakan oleh seeder:
- **URL**: `https://financenotes.nurulmustofa.my.id/login`
- **Email**: `driver@keuangan.test`
- **Password**: `password123`

---

## 🗺️ DAFTAR ALUR SKENARIO PENGUJIAN (URUTAN 1 S/D 10)

```
[1. PWA & Login] ➔ [2. Set Saldo Awal] ➔ [3. Buka Hari Kerja] ➔ [4. Aksi Lapangan & COD] 
       ➔ [5. Voice Memo Intercom] ➔ [6. Utang & Langganan] ➔ [7. Target/Goal Finansial] 
       ➔ [8. Closing & Hitung Fisik Uang] ➔ [9. Analitik Driver & BBM] ➔ [10. Audit & Export]
```

---

### SKENARIO 1: Pengujian PWA (Instal Aplikasi di HP) & Login
**Tujuan**: Memastikan aplikasi dapat diinstal di Google Chrome Android sebagai Web App mandiri (fullscreen tanpa URL bar).

1. Buka Chrome di HP dan ketik: `https://financenotes.nurulmustofa.my.id`
2. **Uji Titik Tiga Chrome**:
   - Ketuk menu titik tiga (⋮) di pojok kanan atas Chrome.
   - **Ekspektasi**: Muncul tulisan **"Instal aplikasi"** (bukan lagi "Aplikasi ini tidak dapat diinstal").
3. **Uji Banner Instal di Halaman**:
   - Perhatikan bagian bawah layar, terdapat kartu: *"Instal Aplikasi Driver di HP"*.
   - Ketuk tombol **"Instal"**.
   - **Ekspektasi**: Muncul prompt konfirmasi native dari Android. Pilih "Instal". Icon *Driver Finance* akan muncul di app drawer/homescreen HP Anda.
4. **Login**:
   - Masukkan Email: `driver@keuangan.test`
   - Masukkan Password: `password123`
   - Centang "Ingat saya" lalu klik **"Masuk ke Dashboard"**.
   - **Ekspektasi**: Berhasil masuk ke Dashboard dengan pesan selamat datang.

---

### SKENARIO 2: Verifikasi Awal & Pengaturan Saldo Modal (Initial Balance)
**Tujuan**: Memastikan semua saldo awal bernilai Rp0, lalu menetapkan modal awal driver sebelum on-bid.

1. **Periksa Tampilan Dashboard**:
   - Net Worth bernilai **Rp 0**.
   - Seluruh akun (Cash, BCA, ShopeePay, GoPay, SeaBank) bernilai **Rp 0**.
   - Muncul kartu peringatan: *"Hari kerja belum dibuka. Buka hari kerja untuk mulai mencatat transaksi driver on-bid."*
2. **Tetapkan Modal Awal Driver (Set Initial Balance)**:
   - Pada kartu ringkasan akun di Dashboard, klik tombol **"Atur Saldo Awal"** (atau modal akun).
   - Masukkan modal uang saku:
     - **Pilih Akun**: `Cash`
     - **Saldo Awal**: `Rp 150.000` (Uang pecahan di dompet untuk modal kembalian COD).
     - Klik **Simpan**.
   - Masukkan saldo rekening:
     - **Pilih Akun**: `BCA`
     - **Saldo Awal**: `Rp 500.000`
     - Klik **Simpan**.
   - Masukkan saldo e-wallet:
     - **Pilih Akun**: `ShopeePay`
     - **Saldo Awal**: `Rp 100.000`
     - Klik **Simpan**.
3. **Ekspektasi Hasil**:
   - Saldo Cash: **Rp 150.000**
   - Saldo BCA: **Rp 500.000**
   - Saldo ShopeePay: **Rp 100.000**
   - **Net Worth Total**: **Rp 750.000** (Presisi utuh Rp1 tanpa selisih).

---

### SKENARIO 3: Pagi Hari - Buka Hari Kerja (Open Work Day)
**Tujuan**: Mengaktifkan sesi kerja lapangan (On-Beat) dan mencatat odometer awal motor.

1. Pada Dashboard bagian atas, klik tombol hijau **"Buka Hari Kerja"**.
2. Masukkan data:
   - **Tanggal**: Hari ini (otomatis terisi)
   - **Odometer Awal (KM)**: `12450`
   - **Target Order Hari Ini**: `18`
   - **Target Pendapatan**: `Rp 180.000`
   - Klik **"Mulai Sesi Kerja"**.
3. **Ekspektasi Hasil**:
   - Status berganti menjadi: **Aktif (On-Beat)**.
   - Terbuka panel tombol aksi cepat operasional driver.

---

### SKENARIO 4: Aksi Cepat Operasional Lapangan (8 Tombol Driver)
**Tujuan**: Menguji pencatatan cepat 1-klik di jalan tanpa repot membuat jurnal manual.

#### 4A. Tarik Tunai ATM (Tarik Uang Bank ke Dompet Fisik)
- Klik tombol **"Tarik Tunai ATM"**.
- Pilih Akun Sumber: `BCA`
- Akun Tujuan: `Cash`
- Jumlah: `Rp 100.000`
- Klik **Simpan**.
- **Ekspektasi**: Saldo BCA menjadi **Rp 400.000**, Saldo Cash menjadi **Rp 250.000**, Net Worth tetap **Rp 750.000**.

#### 4B. Isi Bensin Motor (SPBU Pertamina)
- Klik tombol **"Beli BBM"**.
- Jenis SPBU: `SPBU Pertamina`
- Jenis Bahan Bakar: `Pertalite`
- Total Bayar: `Rp 35.000`
- Jumlah Liter: `3.5`
- Odometer Saat Isi: `12480`
- Sumber Dana: `Cash`
- Klik **Simpan Transaksi BBM**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 215.000**.

#### 4C. Bayar Parkir Resto
- Klik tombol **"Bayar Parkir"**.
- Jumlah: `Rp 2.000`
- Catatan: `Parkir Resto Mie Gacoan`
- Sumber Dana: `Cash`
- Klik **Simpan**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 213.000**.

#### 4D. Pesanan COD ShopeeFood (Alur Kas + Kalkulator Kembalian)
- Klik tombol **"Pesanan COD"**.
- Pilih tab / tombol: **"Terima Pesanan COD Baru"**.
- No. Pesanan: `SPF-9901`
- Tagihan Makanan: `Rp 78.000`
- Klik **"Simpan & Pantau Pesanan"**.
- **Ekspektasi**: Banner pesanan COD aktif muncul di dashboard dengan nilai Rp 78.000.
- **Uji Kalkulator Kembalian**:
  - Klik tombol **"Hitung Kembalian"** pada kartu pesanan aktif.
  - Masukkan Uang Diterima dari Pelanggan: `Rp 100.000`
  - **Ekspektasi**: Kalkulator langsung menampilkan teks hijau: **"Kembalian: Rp 22.000"**.
  - Pilih metode penyelesaian: **"Selesai (Uang Tunai Cash)"**.
  - Klik **"Selesaikan Order"**.
- **Ekspektasi Saldo**: Uang tunai driver menerima Rp100.000 dan mengeluarkan kembalian Rp22.000 (bersih kas masuk Rp78.000). Saldo Cash naik dari Rp 213.000 menjadi **Rp 291.000**.

#### 4E. Pesanan COD dengan Pelanggan Bayar Non-Tunai (QRIS)
- Buat Pesanan COD baru: No. Pesanan `SPF-9902`, Tagihan `Rp 45.000`.
- Di rumah pelanggan, pelanggan membayar via QRIS ShopeePay driver.
- Klik **"Selesaikan Pesanan"** ➔ Pilih metode: **"QRIS (ShopeePay)"**.
- **Ekspektasi**: Saldo ShopeePay bertambah Rp 45.000 menjadi **Rp 145.000**. Saldo Cash tidak berubah.

#### 4F. Pinjamkan Uang Tunai ke Teman Driver (Lend Friend Cash)
- Klik tombol **"Pinjamkan Uang ke Teman"**.
- Nama Teman: `Agus Driver`
- Jumlah: `Rp 20.000`
- Akun Sumber: `Cash`
- Klik **Simpan**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 271.000**. Tercatat otomatis sebagai piutang teman di sistem.

#### 4G. Pengeluaran Insidental Lapangan (Tambal Ban)
- Klik tombol **"Insidental Lapangan"**.
- Kategori: `Tambal Ban / Service Ringan`
- Jumlah: `Rp 15.000`
- Sumber Dana: `Cash`
- Catatan: `Tambal ban tubeless belakang di Dinoyo`
- Klik **Simpan**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 256.000**.

---

### SKENARIO 5: Voice Memo Intercom Helm (Perekaman Cepat Tanpa Ngetik)
**Tujuan**: Menguji pencatatan suara bebas tangan saat mengemudi motor di jalan raya.

1. Di navigasi bawah (mobile) atau di sudut kanan bawah (desktop), klik tombol bulat merah berlogo **Microphone**.
2. Klik **"Mulai Rekam Suara"** (izinkan browser mengakses microphone & GPS).
3. Ucapkan kalimat: *"Beli es teh jumbo tiga ribu rupiah di lampu merah Sulfat"*.
4. Klik **"Selesai & Simpan Suara"**.
5. **Ekspektasi Hasil**:
   - Voice memo tersimpan di list harian.
   - Terdapat audio player yang dapat diputar ulang audionya.
   - Muncul link pin lokasi GPS Google Maps tempat Anda merekam audio tersebut.

---

### SKENARIO 6: Kebutuhan Sehari-Hari, Utang & Cicilan PayLater
**Tujuan**: Menguji pencatatan biaya hidup pribadi, cicilan PayLater, dan langganan bulanan.

#### 6A. Pengeluaran Kebutuhan Sehari-hari
1. Klik tombol **"Makan & Minum"**: Masukkan `Rp 18.000` (Sumber: `Cash`). Saldo Cash menjadi **Rp 238.000**.
2. Klik tombol **"Beli Rokok / Cemilan"**: Masukkan `Rp 25.000` (Sumber: `Cash`). Saldo Cash menjadi **Rp 213.000**.

#### 6B. Utang & Cicilan (Menu Utang di Navbar)
Buka menu **"Utang & Cicilan"** (`/debts`):
1. **Catat SPayLater / TikTok PayLater**:
   - Klik **"Tambah Utang Baru"**.
   - Jenis: `PayLater (Barang)`
   - Nama Utang: `SPayLater Ban Motor Tubeless Depan`
   - Total Pokok: `Rp 240.000`
   - Tenor: `3 Bulan` (Cicilan Rp 80.000 / bulan)
   - Tanggal Jatuh Tempo: Tanggal 15 bulan depan
   - Klik **Simpan**.
   - **Ekspektasi**: Utang tercatat Rp 240.000. Saldo kas/bank tidak berkurang karena barang ditalangi platform PayLater.
2. **Catat Langganan Bulanan (Subscription)**:
   - Pada kartu Langganan Bulanan, klik **"Tambah Langganan"**.
   - Nama: `Paket Data Driver Telkomsel`
   - Biaya per Bulan: `Rp 50.000`
   - Siklus Tagihan: Tanggal 28 setiap bulan
   - Klik **Simpan**.
   - **Ekspektasi**: Widget countdown pembayaran langganan tampil rapi di Dashboard.

---

### SKENARIO 7: Target Finansial (Financial Goal & Safe-to-Spend)
**Tujuan**: Memastikan uang tabungan untuk target tertentu diproteksi dan saldo aman belanja (*Safe to Spend*) terhitung akurat.

1. Di Dashboard, cari kartu **"Target Keuangan (Financial Goals)"**.
2. Klik **"Buat Target Baru"**:
   - Nama Target: `Service CVT & Ganti Oli Mesin`
   - Target Dana: `Rp 90.000`
   - Tanggal Target: 7 hari ke depan
   - Klik **Simpan Target**.
3. **Alokasikan Dana**:
   - Klik tombol **"Alokasikan Dana"** pada kartu target.
   - Ambil dari Akun: `BCA`
   - Jumlah: `Rp 50.000`
   - Klik **Simpan Alokasi**.
4. **Ekspektasi Safe to Spend**:
   - Saldo BCA tetap Rp 400.000, tetapi angka **"Safe to Spend" (Uang Aman Dibelanjakan)** otomatis berkurang Rp 50.000 untuk melindungi dana servis motor agar tidak terpakai foya-foya.

---

### SKENARIO 8: Sore/Malam Hari - Closing Shift & Rekonsiliasi Kas Fisik
**Tujuan**: Menguji proses tutup buku harian, kalkulator lembaran uang tunai fisik di saku, deteksi selisih uang hilang/lebih, dan penguncian hari kerja.

1. Buka Dashboard, pada bagian hari kerja aktif klik tombol oranye: **"Closing Shift Harian"** (atau buka `/closing/{id}`).
2. **Masukkan Data Akhir Performa Driver**:
   - **Odometer Akhir (KM)**: `12560` (Total jarak hari ini = 12.560 - 12.450 = 110 KM).
   - **Jumlah Order Selesai**: `18` Order.
   - **Pendapatan Bersih ShopeeFood**: `Rp 180.000` (Akun Penerima: `BCA`).
   - **Bonus Hub Otomatis**: Sistem otomatis menghitung skema tier ShopeeFood (Tier 2: 18 order mendapatkan tambahan bonus Rp 1.000 x (18-15) = Rp 3.000).
3. **Kalkulator Pecahan Uang Fisik Dompet (Denominations)**:
   - Berdasarkan transaksi di atas, uang tunai fisik yang seharusnya ada di dompet driver adalah **Rp 213.000**.
   - Mari kita hitung fisik uang di saku Anda:
     - Pecahan Rp 100.000 : Masukkan `2` lembar (= Rp 200.000)
     - Pecahan Rp 10.000  : Masukkan `1` lembar (= Rp 10.000)
     - Pecahan Rp 2.000   : Masukkan `1` lembar (= Rp 2.000)
     - Pecahan Rp 1.000   : Masukkan `1` keping/lembar (= Rp 1.000)
     - *(Total Fisik Terhitung: Rp 213.000)*
   - Klik **"Simpan Hitungan Fisik"**.
4. **Verifikasi Status Rekonsiliasi**:
   - **Ekspektasi**: Status kas fisik langsung berstatus **"BALANCED" (Sempurna / Seimbang)** dengan warna hijau cerah!
   - Tombol hijau **"Finalisasi Tutup Buku (Closing Hari Ini)"** aktif dan dapat diklik.
5. **(Opsi Pengujian Selisih Uang - Bila Uang Hilang Rp 3.000)**:
   - Jika Anda mengubah pecahan Rp 1.000 menjadi 0 dan Rp 2.000 menjadi 0 (sehingga fisik hanya Rp 210.000):
   - Status seketika berubah menjadi merah: **"SHORTAGE (Kurang Rp 3.000)"**.
   - Tombol Finalisasi otomatis nonaktif (terkunci).
   - Muncul tombol kuning: **"Sesuaikan Selisih (Adjust Discrepancy)"**.
   - Ketika tombol tersebut diklik, sistem membuat jurnal otomatis *Beban Selisih Kas Hilang* Rp 3.000, status kembali menjadi hijau *Balanced*, dan tombol Finalisasi terbuka kembali.
6. **Finalisasi**:
   - Klik tombol **"Finalisasi Tutup Buku (Closing Hari Ini)"**.
   - **Ekspektasi**: Status sesi hari ini terkunci menjadi **"FINAL (Tutup Buku)"**. Transaksi hari ini telah diaudit dan tidak dapat diubah-ubah secara diam-diam.

---

### SKENARIO 9: Analisis Finansial & Efisiensi Motor Driver
**Tujuan**: Melihat laporan analisis metrik performa driver pasca-closing.

1. **Buka Menu "Analitik Driver"** (`/analytics/driver`):
   - Periksa **KPI Utama**:
     - Total Penghasilan Bersih (Net Earnings).
     - Rata-rata Pendapatan per Jam (*Hourly Rate*).
     - Rasio Keberhasilan Order (100%).
     - Capaian Tier Bonus ShopeeFood.
2. **Buka Menu "Konsumsi BBM"** (`/analytics/fuel`):
   - Periksa **Efisiensi Bahan Bakar**:
     - Total Biaya BBM: Rp 35.000.
     - Total Liter: 3.5 L.
     - Jarak Tempuh: 110 KM (dari 12.450 km ke 12.560 km).
     - Efisiensi Motor: **31.43 KM per Liter**.
     - Grafik Pembelian: 100% SPBU Resmi Pertamina vs 0% Bensin Madura/Eceran.

---

### SKENARIO 10: Audit Log & Export Laporan PDF/CSV
**Tujuan**: Memastikan integritas audit trail finansial dan fitur download rekap bulanan.

1. **Buka Menu "Audit Log"** (`/audit-logs`):
   - Periksa bahwa setiap aksi (Buka Hari Kerja, BBM, COD, Voice Memo, hingga Final Closing) tercatat lengkap secara kronologis dengan IP Address dan timestamp terperinci.
   - Sesuai prinsip *Immutability*, tidak ada data transaksi yang dihapus diam-diam.
2. **Uji Download Laporan CSV**:
   - Di Dashboard atau menu laporan, klik tombol **"Export CSV"** (`/export/transactions/csv`).
   - Buka file `.csv` yang terunduh di Excel atau HP.
   - **Ekspektasi**: Berisi seluruh baris transaksi lengkap dengan debit, kredit, akun, dan kategori.
3. **Uji Rekap PDF**:
   - Buka URL: `https://financenotes.nurulmustofa.my.id/export/report/pdf`
   - **Ekspektasi**: Tampil rekap bulanan siap cetak/simpan PDF dengan ringkasan laba kotor, beban operasional, beban harian, dan laba bersih driver.

---

## 🎯 RINGKASAN CHECKLIST PENGUJIAN AKHIR

| No | Modul / Fitur | Status Uji | Catatan Driver |
|---|---|:---:|---|
| 1 | PWA Install di Chrome Android | [ ] | Ikon DF muncul di home screen tanpa address bar browser |
| 2 | Login & Otentikasi | [ ] | Email `driver@keuangan.test` & `password123` |
| 3 | Modal Saldo Awal (Cash, Bank, E-Wallet) | [ ] | Net worth terhitung otomatis presisi Rp1 |
| 4 | Buka Hari Kerja (Odometer & Target) | [ ] | Sesi On-Beat aktif |
| 5 | Aksi Tarik Tunai ATM | [ ] | Pindah dana BCA ke Cash tanpa selisih |
| 6 | Aksi BBM & Hitung Odometer | [ ] | Tercatat untuk analitik efisiensi km/liter |
| 7 | Pesanan COD & Kalkulator Kembalian | [ ] | Hitung kembalian cepat + penyelesaian Cash/QRIS |
| 8 | Voice Memo Intercom Helm | [ ] | Rekam suara + pin lokasi GPS di Google Maps |
| 9 | Pinjamkan Uang ke Teman Driver | [ ] | Otomatis catat piutang tanpa ganggu kas driver |
| 10 | Utang, PayLater & Langganan Bulanan | [ ] | Countdown jatuh tempo di dashboard |
| 11 | Target Keuangan & Safe to Spend | [ ] | Memproteksi uang tabungan agar tidak terpakai |
| 12 | Hitung Fisik Kas & Penyesuaian Selisih | [ ] | Input denominasi keping/lembar + auto-adjust |
| 13 | Finalisasi Closing Harian | [ ] | Status hari kerja terkunci aman |
| 14 | Analitik Driver & Konsumsi BBM | [ ] | Menampilkan rasio km/liter dan tier bonus |
| 15 | Audit Log & Export CSV / PDF | [ ] | Data riwayat lengkap dan terunduh sempurna |

---
*Dokumen ini dibuat otomatis oleh AI Coding Agent (Google Anti-Gravity) untuk memandu pengujian menyeluruh Super App Driver Finance Malang.*
