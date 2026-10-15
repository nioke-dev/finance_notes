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
[1. PWA & Login] ➔ [2. Set Modal Saldo Awal] ➔ [3. Buka Hari Kerja] ➔ [4. Aksi Lapangan & COD] 
       ➔ [5. Voice Memo Intercom] ➔ [6. Kebutuhan Harian & Utang] ➔ [7. Target/Goal Finansial] 
       ➔ [8. Closing & Hitung Fisik Uang] ➔ [9. Analitik Driver & BBM] ➔ [10. Audit & Export]
```

---

### SKENARIO 1: Pengujian PWA (Instal Aplikasi di HP) & Login
**Tujuan**: Memastikan aplikasi dapat diinstal di Google Chrome Android sebagai Web App mandiri (fullscreen tanpa URL bar browser).

1. Buka Chrome di HP dan ketik: `https://financenotes.nurulmustofa.my.id`
2. **Uji Titik Tiga Chrome**:
   - Ketuk menu titik tiga (⋮) di pojok kanan atas browser Chrome.
   - **Ekspektasi**: Muncul opsi menu **"Instal aplikasi"** (atau **"Instal Driver Finance"**).
3. **Uji Banner Instal di Halaman**:
   - Perhatikan bagian bawah layar (atau di halaman login), terdapat tombol/kartu: *"Instal Aplikasi Driver di HP"*.
   - Ketuk tombol **"Instal"**.
   - **Ekspektasi**: Muncul pop-up konfirmasi native Android. Pilih "Instal". Ikon *Driver Finance* akan terpasang di layar utama HP Anda.
4. **Login ke Sistem**:
   - Masukkan Email: `driver@keuangan.test`
   - Masukkan Password: `password123`
   - Centang "Ingat saya" lalu klik tombol hijau **"Masuk ke Dashboard"**.
   - **Ekspektasi**: Berhasil masuk ke halaman Dashboard.

---

### SKENARIO 2: Verifikasi Dashboard Awal & Atur Saldo Awal (Modal Baseline)
**Tujuan**: Memastikan saldo awal bernilai Rp0, melihat kartu panduan shift, lalu menetapkan modal awal driver.

1. **Periksa Tampilan Awal Dashboard**:
   - Di bagian atas layar, langsung tampil kartu panduan hijau-gelap:
     > *"🌅 **Hari Kerja Belum Dibuka [Siap On-Bid]** — Buka hari kerja untuk mulai mencatat target harian, kilometer odometer awal motor, dan transaksi driver on-bid."*
   - Angka **Total Kekayaan Bersih (Net Worth)** bernilai **Rp 0**.
   - Seluruh akun di daftar rekening bernilai **Rp 0**.

2. **Atur Saldo Modal Awal Rekening & Kas (Set Initial Balance)**:
   - Scroll ke bawah ke bagian **"Daftar Akun Keuangan & Rekening Bank"** (di bawah kartu Net Worth & Safe to Spend).
   - Di sebelah kanan judul bagian tersebut, klik tombol hijau: **`💰 Atur Saldo Awal`**.
   - Jendela pop-up modal bertajuk *"Inisialisasi Saldo Awal (Modal Baseline)"* akan terbuka.

3. **Input Modal 1: Uang Fisik Dompet (Cash)**:
   - Pada dropdown *Pilih Akun yang Belum Dikunci*, pilih: `Cash (CASH • Saat ini: Rp 0)`.
   - Pada kotak *💵 Hitung Lembar & Koin Fisik (Kas Dompet)*, masukkan pecahan uang saku Anda:
     - Pecahan Rp 100.000: ketik `1`
     - Pecahan Rp 50.000: ketik `1`
     - *(Otomatis terhitung: 2 lembar/koin = Rp 150.000)*
   - Atau langsung ketik pada *Nominal Saldo Modal Awal (Rp)*: `150000`
   - Klik tombol hijau: **"Kunci Saldo Awal"**.
   - **Ekspektasi**: Notifikasi sukses muncul. Kartu CASH kini bertuliskan **Modal Awal: Rp 150.000** dengan badge hijau **🔒 Locked**.

4. **Input Modal 2: Rekening Bank (BCA)**:
   - Klik kembali tombol **`💰 Atur Saldo Awal`**.
   - Pada dropdown *Pilih Akun*, pilih: `BCA (BANK • Saat ini: Rp 0)`.
   - Pada input *Nominal Saldo Modal Awal (Rp)*, ketik: `500000`
   - Klik **"Kunci Saldo Awal"**.

5. **Input Modal 3: E-Wallet (ShopeePay)**:
   - Klik kembali tombol **`💰 Atur Saldo Awal`**.
   - Pada dropdown *Pilih Akun*, pilih: `ShopeePay (EWALLET • Saat ini: Rp 0)`.
   - Pada input *Nominal Saldo Modal Awal (Rp)*, ketik: `100000`
   - Klik **"Kunci Saldo Awal"**.

6. **Ekspektasi Hasil**:
   - Saldo Cash: **Rp 150.000**
   - Saldo BCA: **Rp 500.000**
   - Saldo ShopeePay: **Rp 100.000**
   - **Total Kekayaan Bersih (Net Worth)** di atas otomatis terhitung tepat: **Rp 750.000**.

---

### SKENARIO 3: Pagi Hari - Buka Hari Kerja (Open Work Day)
**Tujuan**: Mengaktifkan sesi kerja lapangan (On-Beat) dan mencatat odometer awal motor.

1. Di bagian paling atas Dashboard (pada kartu panduan hijau), klik tombol: **`🌅 Buka Hari Kerja Sekarang`**.
2. Jendela pop-up modal bertajuk *"Buka Shift Hari Kerja Baru"* akan terbuka:
   - **Jenis Hari**: Pilih `Hari Narik Aktif (On-Beat)`
   - **Tanggal Kerja**: Hari ini (otomatis terisi)
   - **Target Order Hari Ini**: Ketik `18`
   - **Target Pendapatan**: Ketik `180000`
   - **Odometer Awal Motor (KM)**: Ketik `12450`
   - Klik tombol hijau: **"Simpan & Buka Shift"**.
3. **Ekspektasi Hasil**:
   - Kartu panduan atas otomatis menghilang.
   - Status shift pada tab On-Bid berganti menjadi: **Aktif (On-Beat)**.
   - Panel tombol aksi operasional narik siap digunakan.

---

### SKENARIO 4: Aksi Cepat Operasional Lapangan (8 Tombol Driver Narik)
**Tujuan**: Menguji pencatatan cepat 1-klik di jalan raya.

> *Pastikan Anda berada di tab: **"🛵 Operasional ShopeeFood Driver (On-Bid)"**.*

#### 4A. Tarik Tunai ATM (Tarik Uang Rekening ke Dompet Fisik)
- Pada barisan *Aksi Operasional Narik*, klik tombol ke-7: **`🏧 Tarik Tunai`**.
- Modal *"Tarik Tunai Cepat (ATM)"* terbuka:
  - **Pilih Akun Bank**: Pilih `BCA`
  - **Nominal Ditarik (Rp)**: Ketik `100000`
  - Klik **"Simpan Penarikan"**.
- **Ekspektasi**: Saldo BCA menjadi **Rp 400.000**, Saldo Cash menjadi **Rp 250.000**, Net Worth tetap **Rp 750.000**.

#### 4B. Isi Bensin Motor (SPBU Pertamina)
- Klik tombol ke-3: **`⛽ Beli Bensin`**.
- Modal *"Catat Pembelian Bensin (BBM)"* terbuka:
  - **Jenis Pembelian BBM**: Pilih `SPBU Resmi (Pertamina / Shell / BP)`
  - **Jenis Bahan Bakar**: Pilih `Pertalite (RON 90)`
  - **Total Pembayaran (Rp)**: Ketik `35000`
  - **Jumlah Liter**: Ketik `3.5`
  - **Odometer Saat Isi (KM)**: Ketik `12480`
  - **Sumber Dana Pembayaran**: Pilih `Cash (Uang Tunai)`
  - Klik **"Simpan Transaksi BBM"**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 215.000**. Kotak metrik *Beban Bensin (BBM)* di atas menampilkan `-Rp 35.000`.

#### 4C. Bayar Parkir Motor
- Klik tombol ke-4: **`🅿️ Parkir Motor`**.
- Modal *"Catat Biaya Parkir"* terbuka:
  - **Nominal Parkir (Rp)**: Ketik `2000`
  - **Catatan / Titik Parkir**: Ketik `Parkir Resto Mie Gacoan`
  - **Sumber Dana Pembayaran**: Pilih `Cash (Uang Tunai)`
  - Klik **"Simpan Biaya Parkir"**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 213.000**.

#### 4D. Pesanan COD ShopeeFood (Alur Kas + Kalkulator Kembalian)
- Klik tombol ke-2: **`💵 Order COD Baru`**.
- Modal *"Terima Pesanan COD Baru"* terbuka:
  - **Nomor Pesanan ShopeeFood**: Ketik `SPF-9901`
  - **Nama Toko / Resto**: Ketik `Ayam Geprek Kak Rose`
  - **Tagihan Asli ShopeeFood (Rp)**: Ketik `78000`
  - Klik **"Simpan & Pantau Pesanan"**.
- **Ekspektasi**: Muncul kartu *"Pesanan COD Aktif di Jalan"* di Dashboard dengan tagihan Rp 78.000.
- **Uji Kalkulator Kembalian**:
  - Pada kartu pesanan `SPF-9901`, klik tombol hijau: **`🧮 Buka Kalkulator & Selesaikan (#SPF-9901)`**.
  - Modal kalkulator kembalian terbuka:
    - Masukkan pada kolom *Uang Tunai Diterima dari Pelanggan (Rp)*: Ketik `100000`
    - **Ekspektasi**: Kotak hijau langsung menghitung: **Kembalian Uang Pelanggan: Rp 22.000**.
    - Pastikan tab terpilih adalah **"💵 Bayar Tunai (Cash)"**.
    - Klik tombol: **"Konfirmasi Selesai Tunai"**.
- **Ekspektasi Saldo**: Kas fisik menerima Rp 100.000 dan mengeluarkan kembalian Rp 22.000 (bersih kas masuk Rp 78.000). Saldo Cash bertambah dari Rp 213.000 menjadi **Rp 291.000**.

#### 4E. Pesanan COD dengan Pelanggan Bayar Non-Tunai (QRIS)
- Klik kembali tombol **`💵 Order COD Baru`**:
  - No. Pesanan: `SPF-9902`
  - Tagihan: `45000`
  - Klik **"Simpan & Pantau Pesanan"**.
- Klik **`🧮 Buka Kalkulator & Selesaikan (#SPF-9902)`**:
  - Di bagian atas modal kalkulator, klik tab: **"📱 Pelanggan Bayar Non-Tunai (QRIS / Transfer)"**.
  - **Rekening Penerima QRIS Driver**: Pilih `ShopeePay`
  - **Nominal Masuk Rekening (Rp)**: Ketik `45000`
  - Klik **"Konfirmasi Selesai via QRIS"**.
- **Ekspektasi**: Saldo ShopeePay bertambah Rp 45.000 (menjadi **Rp 145.000**). Saldo Cash tidak berubah.

#### 4F. Pinjamkan Uang Tunai ke Teman Driver (Lend Friend Cash)
- Klik tombol ke-6: **`🤝 Kas Dipinjam`**.
- Modal *"Pinjamkan Kas ke Teman Driver"* terbuka:
  - **Nama Peminjam / Teman**: Ketik `Agus Driver`
  - **Nominal Dipinjamkan (Rp)**: Ketik `20000`
  - **Pilih Akun Sumber Dana**: Pilih `Cash`
  - Klik **"Catat Pinjaman"**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 271.000**. Piutang teman tercatat otomatis di sistem.

#### 4G. Pengeluaran Insidental Lapangan (Tambal Ban)
- Klik tombol ke-5: **`🛠️ Biaya Lapangan`**.
- Modal *"Pengeluaran Insidental Lapangan"* terbuka:
  - **Nama Pengeluaran / Kebutuhan**: Ketik `Tambal Ban Tubeless Belakang`
  - **Nominal Biaya (Rp)**: Ketik `15000`
  - **Sumber Dana Pembayaran**: Pilih `Cash (Uang Tunai)`
  - Klik **"Simpan Pengeluaran"**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 256.000**.

---

### SKENARIO 5: Voice Memo Intercom Helm (Perekaman Cepat Tanpa Ngetik)
**Tujuan**: Menguji pencatatan suara bebas tangan saat mengemudi motor di jalan raya.

1. Di pojok kanan bawah layar (atau tombol ke-8 `🎙️ Intercom Helm` di grid aksi), klik tombol **Intercom Helm**.
2. Modal *"Voice Memo Intercom Helm"* terbuka:
   - Klik tombol merah besar: **"Mulai Rekam Suara"** (izinkan browser mengakses Microphone & Lokasi).
   - Ucapkan kalimat: *"Beli es teh jumbo tiga ribu rupiah di perempatan Dinoyo"*.
   - Klik **"Selesai & Simpan Suara"**.
3. **Ekspektasi Hasil**:
   - Voice memo tersimpan pada bagian *Kotak Hitam & Memo Suara Helm Hari Ini*.
   - Pemutar audio dapat di-play langsung.
   - Terdapat link tombol GPS Google Maps menuju koordinat lokasi tempat Anda merekam audio tersebut.

---

### SKENARIO 6: Kebutuhan Sehari-Hari & Utang PayLater
**Tujuan**: Menguji pencatatan biaya hidup pribadi di luar on-bid dan manajemen utang.

#### 6A. Pengeluaran Makan & Minum Pribadi
1. Beralih ke tab: **`☕ Kebutuhan Sehari-hari (Di Luar On-Bid)`**.
2. Klik tombol pertama: **`🍜 Makan & Belanja`**.
3. Modal pengeluaran terbuka:
   - **Kategori Pengeluaran**: Pilih `Makan & Minum di Jalan`
   - **Nominal (Rp)**: Ketik `18000`
   - **Metode Pembayaran**: Pilih `Cash`
   - Klik **"Simpan Pengeluaran"**.
   - Saldo Cash berkurang menjadi **Rp 238.000**.
4. Klik kembali tombol **`🍜 Makan & Belanja`**:
   - **Kategori**: Pilih `Makan & Minum di Jalan` (atau rokok/jajan)
   - **Nominal (Rp)**: Ketik `25000`
   - **Metode Pembayaran**: Pilih `Cash`
   - Klik **"Simpan Pengeluaran"**.
   - Saldo Cash berkurang menjadi **Rp 213.000**.

#### 6B. Utang & Cicilan (Menu Utang di Navbar Atas)
Buka menu navigasi **"Utang & PayLater"** (`/debts`):
1. **Catat SPayLater / TikTok PayLater**:
   - Klik tombol: **"Tambah Utang Baru"**.
   - **Jenis Utang**: Pilih `PayLater (Barang)`
   - **Nama / Keterangan Utang**: Ketik `SPayLater Ban Motor Tubeless Depan`
   - **Total Pokok Utang (Rp)**: Ketik `240000`
   - **Tenor Cicilan**: Ketik `3` Bulan (Rp 80.000 / bulan)
   - **Tanggal Jatuh Tempo**: Pilih tanggal 15 bulan depan
   - Klik **"Simpan Utang"**.
   - **Ekspektasi**: Utang tercatat Rp 240.000 tanpa memotong saldo kas/bank driver saat ini.
2. **Catat Langganan Bulanan (Subscription)**:
   - Pada kartu *Langganan Bulanan & Paket Rutin*, klik **"Tambah Langganan"**.
   - **Nama Langganan**: Ketik `Paket Data Driver Telkomsel`
   - **Biaya per Bulan (Rp)**: Ketik `50000`
   - **Tanggal Tagihan Bulanan**: Pilih tanggal 28
   - Klik **"Simpan Langganan"**.
   - **Ekspektasi**: Muncul kartu countdown pembayaran kuota data bulanan.

---

### SKENARIO 7: Target Finansial (Financial Goal & Safe-to-Spend)
**Tujuan**: Memastikan tabungan target servis motor terproteksi dan angka belanja aman (*Safe to Spend*) terhitung akurat.

1. Kembali ke **Dashboard** (`/dashboard`).
2. Scroll ke bagian kartu **"Target Keuangan (Financial Goals)"**:
   - Klik tombol: **"Buat Target Baru"**.
   - **Nama Target**: Ketik `Service CVT & Ganti Oli Mesin`
   - **Target Dana (Rp)**: Ketik `90000`
   - **Tanggal Target Selesai**: Pilih 7 hari ke depan
   - Klik **"Simpan Target"**.
3. **Alokasikan Dana**:
   - Pada kartu target yang baru dibuat, klik tombol **"Alokasikan Dana"**.
   - **Ambil dari Rekening/Akun**: Pilih `BCA`
   - **Nominal Alokasi (Rp)**: Ketik `50000`
   - Klik **"Simpan Alokasi"**.
4. **Ekspektasi Safe to Spend**:
   - Saldo riil BCA tetap Rp 400.000.
   - Namun kartu **Safe-to-Spend (Uang Aman Jajan)** di Dashboard otomatis berkurang Rp 50.000 untuk memproteksi uang servis motor Anda agar tidak terpakai foya-foya.

---

### SKENARIO 8: Sore/Malam Hari - Closing Shift & Rekonsiliasi Kas Fisik
**Tujuan**: Menguji proses tutup buku shift narik, kalkulator hitung fisik uang di saku, deteksi selisih, dan penguncian audit.

1. Buka Dashboard, pada bagian shift aktif klik tombol oranye: **`🌙 Finalisasi Hari (Closing di Kos)`** (atau klik menu navbar *Closing & Rekonsiliasi*).
2. Halaman Closing Shift harian akan terbuka:
   - **Odometer Akhir (KM)**: Ketik `12560` (Total jarak tempuh hari ini = 12.560 - 12.450 = 110 KM).
   - **Jumlah Order Selesai**: Ketik `18` Order.
   - **Pendapatan Bersih ShopeeFood**: Ketik `180000` (Pilih Akun Penerima: `BCA`).
   - **Bonus Hub Shopee**: Sistem otomatis menghitung skema tier ShopeeFood Malang (Tier 2: 18 order mendapatkan tambahan bonus Rp 1.000 x (18-15) = Rp 3.000).
3. **Kalkulator Pecahan Uang Fisik Dompet (Denominations)**:
   - Berdasarkan seluruh mutasi di atas, uang kas fisik yang seharusnya ada di dompet Anda adalah tepat **Rp 213.000**.
   - Masukkan hitungan uang fisik di dompet Anda pada kolom pecahan:
     - Pecahan Rp 100.000 : Ketik `2` lembar (= Rp 200.000)
     - Pecahan Rp 10.000  : Ketik `1` lembar (= Rp 10.000)
     - Pecahan Rp 2.000   : Ketik `1` lembar (= Rp 2.000)
     - Pecahan Rp 1.000   : Ketik `1` keping/lembar (= Rp 1.000)
     - *(Total Fisik Terhitung: Rp 213.000)*
   - Klik tombol: **"Simpan Hitungan Fisik"**.
4. **Verifikasi Status Rekonsiliasi**:
   - **Ekspektasi**: Status kas fisik langsung berstatus **"BALANCED" (Sempurna / Seimbang)** berlatar hijau cerah!
   - Tombol hijau **"Finalisasi Tutup Buku (Closing Hari Ini)"** aktif dan siap diklik.
5. **(Uji Deteksi Selisih Uang Kurang - Simulasi Uang Hilang Rp 3.000)**:
   - Jika Anda mengubah pecahan Rp 1.000 menjadi 0 dan Rp 2.000 menjadi 0 (sehingga fisik hanya Rp 210.000):
   - Klik *Simpan Hitungan Fisik*.
   - Status seketika berubah menjadi merah: **"SHORTAGE (Kurang Rp 3.000)"**.
   - Tombol Finalisasi otomatis terkunci (disabled).
   - Muncul tombol kuning: **"Sesuaikan Selisih (Adjust Discrepancy)"**.
   - Klik tombol penyesuaian tersebut ➔ sistem mencatat jurnal *Beban Selisih Kas Hilang* Rp 3.000, status kembali menjadi hijau *Balanced*, dan tombol Finalisasi terbuka kembali.
6. **Finalisasi**:
   - Klik tombol hijau: **"Finalisasi Tutup Buku (Closing Hari Ini)"**.
   - **Ekspektasi**: Status sesi kerja hari ini resmi terkunci menjadi **"FINAL"**. Data hari ini telah tersimpan permanen dan tidak dapat diubah-ubah diam-diam.

---

### SKENARIO 9: Analisis Finansial & Efisiensi Motor Driver
**Tujuan**: Melihat laporan analisis performa driver pasca-closing.

1. **Buka Menu "Analitik Driver"** (`/analytics/driver`):
   - Periksa **KPI Utama**:
     - Total Penghasilan Bersih Narik (Net Earnings).
     - Rata-rata Pendapatan per Jam (*Hourly Rate*).
     - Rasio Keberhasilan Order (100%).
     - Capaian Tier Bonus ShopeeFood.
2. **Buka Menu "Konsumsi BBM"** (`/analytics/fuel`):
   - Periksa **Efisiensi Bahan Bakar**:
     - Total Biaya BBM: Rp 35.000.
     - Total Liter: 3.5 L.
     - Jarak Tempuh: 110 KM (dari 12.450 km ke 12.560 km).
     - Efisiensi Motor: **31.43 KM per Liter**.
     - Grafik Pembelian: 100% SPBU Resmi Pertamina vs 0% Bensin Madura.

---

### SKENARIO 10: Audit Log & Export Laporan PDF/CSV
**Tujuan**: Memastikan transparansi jejak riwayat audit dan fitur download laporan.

1. **Buka Menu "Audit Log"** (`/audit-logs`):
   - Periksa bahwa setiap aksi (Buka Hari Kerja, BBM, COD, Voice Memo, hingga Final Closing) tercatat lengkap secara kronologis dengan IP Address dan timestamp terperinci.
2. **Uji Download Laporan CSV**:
   - Di Dashboard atau menu laporan, klik tombol **"Export CSV"** (`/export/transactions/csv`).
   - File `.csv` terunduh dan dapat dibuka di Excel atau HP.
3. **Uji Rekap Cetak PDF**:
   - Buka URL: `https://financenotes.nurulmustofa.my.id/export/report/pdf`
   - Tampil rekap bulanan siap cetak PDF dengan ringkasan laba kotor, beban operasional motor, dan laba bersih driver.

---
*Dokumen ini diverifikasi 100% sesuai dengan kode tampilan riil Super App Driver Finance Malang.*
