# PANDUAN PENGUJIAN LENGKAP END-TO-END (UAT)
## SUPER APP KEUANGAN PRIBADI & SHOPEEFOOD DRIVER MALANG
**Status Database**: Bersih dari 0 (Seeded Clean State Rp0)  
**Target Server**: [https://financenotes.nurulmustofa.my.id](https://financenotes.nurulmustofa.my.id)

---

## 📌 KREDENSIAL LOGIN PENGUJIAN
Gunakan akun pengujian yang telah disiapkan seeder:
- **URL**: `https://financenotes.nurulmustofa.my.id/login`
- **Email**: `driver@keuangan.test`
- **Password**: `password123`

---

## 🗺️ PETA URUTAN PENGUJIAN DARI NOL (TAHAP 1 S/D 10)

```
[1. Instal PWA & Login] 
       ⬇
[2. KELOLA PORTOFOLIO DARI 0: Tambah E-Wallet, Tutup Akun Tak Terpakai, Set Modal, & Daftarkan PayLater Berjalan]
       ⬇
[3. Buka Hari Kerja (On-Beat)] ➔ [4. Aksi Lapangan Driver & Pesanan COD] 
       ⬇
[5. Intercom Helm Voice Memo] ➔ [6. Kebutuhan Hidup Harian & Langganan Bulanan] 
       ⬇
[7. Target Finansial (Goals & Safe to Spend)] ➔ [8. Malam Hari: Closing Shift & Hitung Fisik Kas] 
       ⬇
[9. Analitik Driver & Konsumsi BBM] ➔ [10. Audit Trail Log & Cetak Rekap PDF/CSV]
```

---

### SKENARIO 1: Pengujian Instalasi PWA & Login
**Tujuan**: Memverifikasi aplikasi dapat diinstal di HP Android dan login pertama kali.

1. Buka browser Chrome di HP Anda: `https://financenotes.nurulmustofa.my.id`
2. **Uji Menu Titik Tiga (⋮) Chrome**:
   - Ketuk titik tiga di pojok kanan atas Chrome.
   - **Ekspektasi**: Muncul opsi **"Instal aplikasi"** (atau "Instal Driver Finance").
3. **Uji Tombol Instal di Halaman**:
   - Pada halaman login, terdapat tombol: *"Instal Aplikasi Driver di HP"*.
   - Ketuk tombol tersebut, lalu pilih "Instal" pada prompt Android. Ikon Driver Finance akan muncul di layar utama HP Anda.
4. **Login**:
   - Masukkan Email: `driver@keuangan.test`
   - Masukkan Password: `password123`
   - Centang "Ingat saya" lalu klik **"Masuk ke Dashboard"**.
   - **Ekspektasi**: Berhasil masuk ke Dashboard utama.

---

### SKENARIO 2: Manajemen Portofolio Finansial Pribadi dari Nol
**Tujuan**: Menyesuaikan ekosistem keuangan sesuai milik pribadi Anda (menambah e-wallet baru, menutup akun tak terpakai, mengisi saldo modal awal, dan mendaftarkan utang PayLater berjalan dari berbagai platform).

#### 2A. Menutup / Mengarsipkan Rekening Bawaan yang Tidak Digunakan
1. Di Dashboard, scroll ke bagian **"Daftar Akun Keuangan & Rekening Bank"**.
2. Anda akan melihat kartu-kartu akun bawaan (BCA, SeaBank, ShopeePay, GoPay, GoPay Merchant, Cash, Flazz).
3. Cari kartu **"GoPay Merchant"** (saldo Rp 0).
4. Klik tombol: **`📦 Tutup Rekening`** di kartu tersebut, lalu konfirmasi OK.
5. **Ekspektasi**: Rekening GoPay Merchant diarsipkan sehingga daftar akun Anda rapi dan hanya memuat akun yang benar-benar Anda pakai.

#### 2B. Menambahkan E-Wallet Baru (Misal: DANA / OVO)
1. Di header bagian *Daftar Akun Keuangan & Rekening Bank*, klik tombol biru: **`➕ Tambah Rekening Baru`**.
2. Modal *"➕ Buka Rekening / Dompet Baru"* terbuka:
   - **Nama Rekening / Akun**: Ketik `DANA`
   - **Tipe Akun**: Pilih `E-Wallet (Digital)`
   - **Saldo Modal Awal Rekening (Rp)**: Ketik `50000`
   - Klik tombol biru: **"Simpan Rekening Baru"**.
3. **Ekspektasi**:
   - Akun `DANA` langsung muncul di grid dengan saldo Rp 50.000 dan status modal **🔒 Locked**.
   - Angka Net Worth di atas langsung naik menjadi Rp 50.000.

#### 2C. Menetapkan Saldo Modal Awal Rekening Utama (Cash, BCA, ShopeePay)
1. Klik tombol hijau: **`💰 Atur Saldo Awal`**.
2. Modal *"Inisialisasi Saldo Awal (Modal Baseline)"* terbuka.
3. **Input Modal Kas Dompet (Cash)**:
   - Pada dropdown *Pilih Akun yang Belum Dikunci*, pilih: `Cash (CASH • Saat ini: Rp 0)`.
   - Pada kotak *💵 Hitung Lembar & Koin Fisik*, masukkan pecahan:
     - Rp 100.000: ketik `1`
     - Rp 50.000: ketik `1`
     - *(Otomatis terhitung Rp 150.000)*
   - Atau langsung ketik di kolom *Nominal Saldo Modal Awal (Rp)*: `150000`
   - Klik tombol hijau: **"Kunci Saldo Awal"**.
4. **Input Modal Rekening Bank (BCA)**:
   - Klik kembali **`💰 Atur Saldo Awal`**.
   - Pilih Akun: `BCA (BANK • Saat ini: Rp 0)`.
   - Nominal Saldo Modal Awal: Ketik `500000`.
   - Klik **"Kunci Saldo Awal"**.
5. **Input Modal E-Wallet (ShopeePay)**:
   - Klik kembali **`💰 Atur Saldo Awal`**.
   - Pilih Akun: `ShopeePay (EWALLET • Saat ini: Rp 0)`.
   - Nominal Saldo Modal Awal: Ketik `100000`.
   - Klik **"Kunci Saldo Awal"**.
6. **Ekspektasi Saldo**:
   - Saldo DANA: Rp 50.000
   - Saldo Cash: Rp 150.000
   - Saldo BCA: Rp 500.000
   - Saldo ShopeePay: Rp 100.000
   - **Total Aset Liquid**: Rp 800.000.

#### 2D. Mendaftarkan Tagihan PayLater yang Sedang Berjalan (Dari Berbagai Platform)
*Sebelum mulai narik, daftarkan cicilan/PayLater yang sudah Anda miliki dari sebelum memakai aplikasi ini.*

1. Buka menu navbar: **"Utang & PayLater"** (`/debts`).
2. Di pojok kanan atas, klik tombol ungu: **`➕ Catat Tagihan Berjalan`**.
3. Modal *"Catat Tagihan Utang Berjalan"* terbuka:
   - **Daftarkan Tagihan 1: TikTok PayLater**:
     - *Pilih Platform Utang*: Pilih `🎵 TikTok PayLater`
     - *Nama / Keterangan Tagihan*: Ketik `Cicilan Sepatu Touring TikTok`
     - *Total Tagihan (Rp)*: Ketik `120000`
     - *Tenor*: Ketik `2` Bulan (Rp 60.000/bulan)
     - *Jatuh Tempo Pembayaran*: Pilih tanggal 20 bulan depan
     - Klik **"Simpan Tagihan Berjalan"**.
4. Klik kembali tombol ungu: **`➕ Catat Tagihan Berjalan`**:
   - **Daftarkan Tagihan 2: Kredivo / SPayLater**:
     - *Pilih Platform Utang*: Pilih `⚡ Kredivo PayLater` (atau SPayLater)
     - *Nama / Keterangan Tagihan*: Ketik `Kredivo Ban Belakang & Service`
     - *Total Tagihan (Rp)*: Ketik `180000`
     - *Tenor*: Ketik `3` Bulan (Rp 60.000/bulan)
     - *Jatuh Tempo*: Pilih tanggal 10 bulan depan
     - Klik **"Simpan Tagihan Berjalan"**.
5. **Ekspektasi Portofolio Liabilitas**:
   - Total Utang PayLater terakumulasi tepat: **Rp 300.000** (Rp 120.000 + Rp 180.000).
   - Kembali ke **Dashboard** (`/dashboard`):
     - Aset Liquid: Rp 800.000
     - Total Utang: Rp 300.000
     - **Total Kekayaan Bersih (Net Worth)**: Rp 800.000 - Rp 300.000 = **Rp 500.000** (Presisi utuh Rp1).

---

### SKENARIO 3: Pagi Hari - Buka Hari Kerja (Open Work Day)
**Tujuan**: Mengaktifkan sesi on-bid kerja lapangan dan mencatat odometer awal motor.

1. Di bagian paling atas Dashboard, perhatikan kartu panduan:
   > *"🌅 **Hari Kerja Belum Dibuka [Siap On-Bid]** — Buka hari kerja untuk mulai mencatat target harian, kilometer odometer awal motor, dan transaksi driver on-bid."*
2. Klik tombol hijau pada kartu tersebut: **`🌅 Buka Hari Kerja Sekarang`**.
3. Modal *"Buka Shift Hari Kerja Baru"* terbuka:
   - **Jenis Hari**: `Hari Narik Aktif (On-Beat)`
   - **Target Order Hari Ini**: Ketik `18`
   - **Target Pendapatan**: Ketik `180000`
   - **Odometer Awal Motor (KM)**: Ketik `12450`
   - Klik **"Simpan & Buka Shift"**.
4. **Ekspektasi Hasil**:
   - Kartu panduan atas otomatis menghilang.
   - Status shift pada tab on-bid berganti menjadi: **Aktif (On-Beat)**.
   - Tombol operasional narik di bawahnya aktif digunakan.

---

### SKENARIO 4: Aksi Cepat Operasional Lapangan (8 Tombol Driver Narik)
**Tujuan**: Mencatat pengeluaran bensin, parkir, dan order COD secara cepat di jalan.

> *Pastikan Anda berada di tab: **"🛵 Operasional ShopeeFood Driver (On-Bid)"**.*

#### 4A. Tarik Tunai ATM (Tarik Uang Bank ke Dompet Fisik)
- Klik tombol ke-7: **`🏧 Tarik Tunai`**.
- Pilih Akun Bank: `BCA`
- Nominal Ditarik (Rp): `100000`
- Klik **"Simpan Penarikan"**.
- **Ekspektasi**: Saldo BCA menjadi **Rp 400.000**, Saldo Cash bertambah menjadi **Rp 250.000**, Net Worth tetap aman Rp 500.000.

#### 4B. Isi Bensin Motor di SPBU
- Klik tombol ke-3: **`⛽ Beli Bensin`**.
- Jenis Pembelian: `SPBU Resmi (Pertamina / Shell / BP)`
- Jenis Bahan Bakar: `Pertalite (RON 90)`
- Total Pembayaran (Rp): `35000`
- Jumlah Liter: `3.5`
- Odometer Saat Isi (KM): `12480`
- Sumber Dana: `Cash (Uang Tunai)`
- Klik **"Simpan Transaksi BBM"**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 215.000**.

#### 4C. Bayar Parkir Motor Resto
- Klik tombol ke-4: **`🅿️ Parkir Motor`**.
- Nominal Parkir: `2000`
- Catatan: `Parkir Resto Mie Gacoan`
- Sumber Dana: `Cash (Uang Tunai)`
- Klik **"Simpan Biaya Parkir"**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 213.000**.

#### 4D. Pesanan COD ShopeeFood (Alur Kas & Kalkulator Kembalian)
- Klik tombol ke-2: **`💵 Order COD Baru`**:
  - No. Pesanan: `SPF-9901`
  - Nama Toko / Resto: `Ayam Geprek Kak Rose`
  - Tagihan Asli Shopee: `78000`
  - Klik **"Simpan & Pantau Pesanan"**.
- Kartu pesanan aktif muncul di Dashboard.
- **Uji Kalkulator Kembalian**:
  - Klik tombol hijau: **`🧮 Buka Kalkulator & Selesaikan (#SPF-9901)`**.
  - Masukkan *Uang Tunai Diterima dari Pelanggan (Rp)*: Ketik `100000`.
  - **Ekspektasi**: Kalkulator seketika menghitung **Kembalian Uang Pelanggan: Rp 22.000**.
  - Pastikan tab adalah **"💵 Bayar Tunai (Cash)"**, lalu klik **"Konfirmasi Selesai Tunai"**.
- **Ekspektasi Saldo**: Kas fisik menerima Rp 100.000 dan mengeluarkan kembalian Rp 22.000 (bersih kas masuk Rp 78.000). Saldo Cash bertambah dari Rp 213.000 menjadi **Rp 291.000**.

#### 4E. Pesanan COD Pelanggan Bayar Non-Tunai (QRIS)
- Klik tombol **`💵 Order COD Baru`**:
  - No. Pesanan: `SPF-9902`
  - Tagihan: `45000`
  - Klik **"Simpan & Pantau Pesanan"**.
- Klik **`🧮 Buka Kalkulator & Selesaikan (#SPF-9902)`**:
  - Beralih ke tab: **"📱 Pelanggan Bayar Non-Tunai (QRIS / Transfer)"**.
  - Rekening Penerima: Pilih `ShopeePay`
  - Nominal Masuk: `45000`
  - Klik **"Konfirmasi Selesai via QRIS"**.
- **Ekspektasi**: Saldo ShopeePay bertambah Rp 45.000 (menjadi **Rp 145.000**). Kas fisik tidak berubah.

#### 4F. Pinjamkan Uang Tunai ke Teman Driver
- Klik tombol ke-6: **`🤝 Kas Dipinjam`**.
- Nama Peminjam: `Agus Driver`
- Nominal (Rp): `20000`
- Sumber Dana: `Cash`
- Klik **"Catat Pinjaman"**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 271.000**. Piutang Agus Driver tercatat otomatis di sistem.

#### 4G. Pengeluaran Insidental Lapangan (Tambal Ban)
- Klik tombol ke-5: **`🛠️ Biaya Lapangan`**.
- Nama Kebutuhan: `Tambal Ban Tubeless Belakang`
- Nominal Biaya: `15000`
- Sumber Dana: `Cash`
- Klik **"Simpan Pengeluaran"**.
- **Ekspektasi**: Saldo Cash berkurang menjadi **Rp 256.000**.

---

### SKENARIO 5: Voice Memo Intercom Helm (Rekam Bebas Tangan + GPS)
**Tujuan**: Menguji pencatatan suara cepat saat di atas motor.

1. Klik tombol ke-8: **`🎙️ Intercom Helm`** (atau tombol bulat merah di pojok kanan bawah layar).
2. Modal rekaman terbuka:
   - Klik tombol merah: **"Mulai Rekam Suara"** (izinkan akses microphone & GPS).
   - Ucapkan: *"Beli es teh jumbo tiga ribu rupiah di perempatan Dinoyo"*.
   - Klik **"Selesai & Simpan Suara"**.
3. **Ekspektasi Hasil**:
   - Voice memo tersimpan di list *Kotak Hitam & Memo Suara Helm Hari Ini*.
   - Player audio dapat diputar ulang dan ada tombol link pin Google Maps menuju lokasi perekaman.

---

### SKENARIO 6: Kebutuhan Sehari-Hari & Tambah Belanja PayLater Baru
**Tujuan**: Menguji pencatatan biaya hidup pribadi dan belanja barang baru via PayLater saat on-bid.

#### 6A. Pengeluaran Makan & Minum Pribadi
1. Di Dashboard, beralih ke tab: **`☕ Kebutuhan Sehari-hari (Di Luar On-Bid)`**.
2. Klik tombol pertama: **`🍜 Makan & Belanja`**:
   - Kategori: `Makan & Minum di Jalan`
   - Nominal (Rp): `18000`
   - Metode Pembayaran: `Cash`
   - Klik **"Simpan Pengeluaran"**. (Saldo Cash menjadi Rp 238.000).
3. Klik kembali **`🍜 Makan & Belanja`**:
   - Kategori: `Makan & Minum di Jalan` (rokok/kopi)
   - Nominal (Rp): `25000`
   - Metode: `Cash`
   - Klik **"Simpan Pengeluaran"**. (Saldo Cash menjadi Rp 213.000).

#### 6B. Menambah Belanja Barang Baru via PayLater
1. Masih di tab *Kebutuhan Sehari-hari*, klik tombol ke-4: **`🛍️ Belanja PayLater`**.
2. Modal *"Beli Barang via PayLater"* terbuka:
   - **Pilih Platform**: Pilih `Shopee PayLater (SPayLater)`
   - **Nama Barang / Belanja**: Ketik `Jas Hujan Motor Axio`
   - **Total Harga Barang (Rp)**: Ketik `150000`
   - **Pilihan Tenor**: Pilih `3 Bulan Cicilan`
   - Klik **"Simpan Tagihan PayLater"**.
3. **Ekspektasi**: Tagihan SPayLater bertambah Rp 150.000 tanpa memotong saldo kas/bank driver saat ini.

#### 6C. Menambah Langganan Rutin (Subscription)
1. Buka menu **"Utang & PayLater"** (`/debts`).
2. Pada bagian *Langganan Bulanan & Paket Rutin*, klik **"Tambah Langganan"**:
   - Nama Langganan: `Paket Data Driver Telkomsel`
   - Biaya per Bulan: `50000`
   - Tanggal Tagihan: `28`
   - Klik **"Simpan Langganan"**.
3. **Ekspektasi**: Widget countdown pembayaran kuota bulanan tampil rapi di Dashboard.

---

### SKENARIO 7: Target Finansial (Financial Goal & Safe-to-Spend)
**Tujuan**: Memastikan uang tabungan untuk target servis motor diproteksi dari pengeluaran jajan.

1. Buka **Dashboard** (`/dashboard`), scroll ke kartu **"Target Keuangan (Financial Goals)"**.
2. Klik **"Buat Target Baru"**:
   - Nama Target: `Service CVT & Ganti Oli Mesin`
   - Target Dana (Rp): `90000`
   - Tanggal Target Selesai: Pilih 7 hari ke depan
   - Klik **"Simpan Target"**.
3. **Alokasikan Dana**:
   - Pada kartu target yang baru dibuat, klik **"Alokasikan Dana"**.
   - Ambil dari Rekening: Pilih `BCA`
   - Nominal Alokasi: Ketik `50000`
   - Klik **"Simpan Alokasi"**.
4. **Ekspektasi Safe to Spend**:
   - Saldo riil BCA tetap Rp 400.000.
   - Namun kartu **Safe-to-Spend (Uang Aman Jajan)** di Dashboard otomatis berkurang Rp 50.000 untuk mengunci uang servis agar tidak terpakai foya-foya.

---

### SKENARIO 8: Sore/Malam Hari - Closing Shift & Rekonsiliasi Kas Fisik
**Tujuan**: Menguji proses tutup buku harian, hitung fisik uang di saku, deteksi selisih, dan penguncian audit.

1. Pada Dashboard tab on-bid, klik tombol oranye: **`🌙 Finalisasi Hari (Closing di Kos)`** (atau klik menu *Closing & Rekonsiliasi*).
2. Halaman Closing Shift harian terbuka:
   - **Odometer Akhir (KM)**: Ketik `12560` (Total jarak tempuh = 12.560 - 12.450 = 110 KM).
   - **Jumlah Order Selesai**: Ketik `18` Order.
   - **Pendapatan Bersih ShopeeFood**: Ketik `180000` (Pilih Akun Penerima: `BCA`).
   - **Bonus Hub Otomatis**: Sistem menghitung bonus tier ShopeeFood Malang (Tier 2: 18 order mendapatkan tambahan bonus Rp 1.000 x (18-15) = Rp 3.000).
3. **Kalkulator Pecahan Uang Fisik Dompet (Denominations)**:
   - Berdasarkan mutasi di atas, uang kas fisik yang seharusnya ada di dompet Anda adalah tepat **Rp 213.000**.
   - Masukkan pecahan fisik pada kolom:
     - Rp 100.000 : Ketik `2` lembar (= Rp 200.000)
     - Rp 10.000  : Ketik `1` lembar (= Rp 10.000)
     - Rp 2.000   : Ketik `1` lembar (= Rp 2.000)
     - Rp 1.000   : Ketik `1` keping/lembar (= Rp 1.000)
     - *(Total Fisik Terhitung: Rp 213.000)*
   - Klik tombol: **"Simpan Hitungan Fisik"**.
4. **Verifikasi Status Rekonsiliasi**:
   - **Ekspektasi**: Status kas fisik langsung berstatus **"BALANCED" (Sempurna / Seimbang)** berlatar hijau cerah!
   - Tombol hijau **"Finalisasi Tutup Buku (Closing Hari Ini)"** aktif.
5. **(Simulasi Deteksi Selisih Uang Kurang - Uang Hilang Rp 3.000)**:
   - Jika Anda mengubah pecahan Rp 1.000 menjadi 0 dan Rp 2.000 menjadi 0 (sehingga fisik hanya Rp 210.000):
   - Klik *Simpan Hitungan Fisik*.
   - Status berubah menjadi merah: **"SHORTAGE (Kurang Rp 3.000)"**.
   - Tombol Finalisasi terkunci.
   - Muncul tombol kuning: **"Sesuaikan Selisih (Adjust Discrepancy)"**.
   - Klik tombol penyesuaian tersebut ➔ sistem mencatat jurnal *Beban Selisih Kas Hilang* Rp 3.000, status kembali menjadi hijau *Balanced*, dan tombol Finalisasi terbuka kembali.
6. **Finalisasi**:
   - Klik tombol hijau: **"Finalisasi Tutup Buku (Closing Hari Ini)"**.
   - **Ekspektasi**: Status sesi kerja resmi terkunci menjadi **"FINAL"**. Data tidak dapat diubah-ubah diam-diam.

---

### SKENARIO 9: Analisis Finansial & Efisiensi Motor Driver
**Tujuan**: Melihat laporan performa driver pasca-closing.

1. **Buka Menu "Analitik Driver"** (`/analytics/driver`):
   - Periksa **KPI**:
     - Total Penghasilan Bersih Narik (Net Earnings).
     - Rata-rata Pendapatan per Jam (*Hourly Rate*).
     - Rasio Keberhasilan Order (100%).
     - Capaian Tier Bonus ShopeeFood.
2. **Buka Menu "Konsumsi BBM"** (`/analytics/fuel`):
   - Periksa **Efisiensi BBM**:
     - Total Biaya BBM: Rp 35.000.
     - Total Liter: 3.5 L.
     - Jarak Tempuh: 110 KM (12.450 km ke 12.560 km).
     - Efisiensi: **31.43 KM per Liter**.
     - Grafik Pembelian: 100% SPBU Resmi Pertamina vs 0% Madura.

---

### SKENARIO 10: Audit Log & Cetak Rekap PDF/CSV
**Tujuan**: Memastikan transparansi jejak audit dan fitur ekspor laporan.

1. **Buka Menu "Audit Log"** (`/audit-logs`):
   - Seluruh mutasi dari buka akun, modal awal, belanja, COD, hingga finalisasi closing tercatat permanen dengan timestamp terperinci.
2. **Download Laporan CSV**:
   - Klik tombol **"Export CSV"** (`/export/transactions/csv`) untuk mengunduh rekap mutasi ke format spreadsheet Excel.
3. **Download Rekap PDF**:
   - Buka URL: `https://financenotes.nurulmustofa.my.id/export/report/pdf`
   - Tampil rekap bulanan siap cetak PDF dengan ringkasan laba kotor, beban operasional motor, dan laba bersih driver.

---
*Dokumen UAT ini telah dikalibrasi 100% memuat proses manajemen portofolio awal secara utuh dan sesuai fitur riil Super App Driver Finance Malang.*
