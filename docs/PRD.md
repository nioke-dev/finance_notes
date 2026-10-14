# PRODUCT REQUIREMENT DOCUMENT (PRD.md)
**Aplikasi:** Keuangan Pribadi & ShopeeFood Driver  
**Versi:** 1.0.0 (Spesifikasi Permanen)  
**Terakhir Diperbarui:** 14 Oktober 2026  

---

## 1. Visi & Tujuan Produk
Membangun platform terintegrasi (Web Laravel & Mobile Flutter) untuk mengelola keuangan pribadi harian sekaligus memonitor operasional dan pendapatan sebagai mitra pengemudi ShopeeFood di Malang secara akurat, cepat, dan transparan.

### Sasaran Utama:
1. **Presisi Finansial**: Akurasi pencatatan hingga Rp1 tanpa pembulatan atau toleransi tersembunyi.
2. **Kecepatan Input Lapangan**: Form input cepat 3–10 detik untuk transaksi on-beat (parkir, bensin, tip).
3. **Audit Trail Mutlak**: Setiap koreksi, keterlambatan input, atau pembatalan wajib mencatat riwayat nilai lama, nilai baru, timestamp, dan alasan.
4. **Rekonsiliasi Transparan**: Membedakan tegas antara Saldo Buku (Ledger) dan Saldo Fisik/Aktual di rekening.
5. **Dukungan Investigasi Nyata**: Fitur Voice Memo pendek dari intercom helm untuk menangkap konteks lapangan tanpa harus mengetik panjang.

---

## 2. Arsitektur Pengguna & Perangkat
- **Pengguna Tunggal (Single User)**: Dirancang khusus untuk pemilik produk (driver pribadi).
- **Web App (Laravel 11 + Blade Tailwind)**:
  - Berfungsi sebagai **Super App / Induk Sistem**.
  - Mengelola master akun, kategori dinamis, master utang, laporan cash flow bulanan, analitik performa driver (grafik visual), rekonsiliasi harian, audit log lengkap, dan audio player untuk memutar rekaman Voice Memo.
  - Tampilan responsif di desktop maupun layar HP.
- **Mobile Client (Flutter)**:
  - Berfungsi sebagai **Quick Action Client** untuk di jalan (on-beat).
  - Berfokus pada: Quick Parkir (GPS), Quick Bensin, Voice Memo 1-tap, Snapshot Progres On-Beat All-in-One, dan Form Closing Denominasi Uang Fisik.

---

## 3. Modul Kebutuhan Rinci

### Modul A: Master Akun Keuangan & Ledger
1. **Daftar Akun Utama**:
   - `BCA` (Bank Operasional Pribadi / Belanja)
   - `SeaBank` (Bank Tabungan / Pinjaman)
   - `ShopeePay` (E-Wallet Penampung Pendapatan ShopeeFood)
   - `GoPay` (E-Wallet Operasional)
   - `GoPay Merchant` (Penampung Saldo QRIS Pelanggan)
   - `Cash / Tunai` (Uang Fisik di Kantong/Dompet)
   - `e-money / Flazz / KTM` (Uang Elektronik Transportasi/Tol)
2. **Inisialisasi & Saldo Awal**:
   - Setiap akun memiliki saldo awal manual saat pertama kali aplikasi dikonfigurasi.
   - Saldo awal bukan pendapatan.
   - Perubahan saldo awal setelah disetup wajib mencatat audit trail (alasan & timestamp).
3. **Prinsip Ledger**:
   - Multi-entry / Linked Transactions.
   - Transfer antar-akun (misal BCA -> ShopeePay Rp100.000, admin Rp1.000):
     - BCA berkurang Rp101.000.
     - ShopeePay bertambah Rp100.000.
     - Biaya Admin Rp1.000 dicatat sebagai Beban Pengeluaran.
   - Tidak ada hard delete pada transaksi; pembatalan dilakukan dengan transaksi pembalik (reversal) bertanda audit trail.

---

### Modul B: Operasional ShopeeFood & Formula Bonus Hub
1. **Komponen Pendapatan Resmi ShopeeFood**:
   Hanya terdiri dari 3 komponen:
   $$\text{Total ShopeeFood} = \text{Pendapatan Hub} + \text{Bonus Hub} + \text{Tip Pelanggan (Shopee)}$$
2. **Formula Tingkatan Bonus Hub (Murni Bertingkat / Progresif)**:
   - Order 1 – 15: Rp0 / order
   - Order 16 – 20: Rp1.000 / order (maksimal Rp5.000)
   - Order 21 – 25: Rp1.500 / order (maksimal Rp7.500)
   - Order 26+: Rp2.000 / order
   *Skema ini wajib dinamis (dapat dikonfigurasi lewat database/web).*
3. **Syarat Kelayakan & Evaluasi Performa (Jam 20.00 Cut-Off)**:
   - Jam order eligible: Hanya pesanan yang masuk rentang **10.00 – 20.00**.
   - Syarat performa mutlak:
     1. Total waktu online $\ge$ 9 jam.
     2. Waktu online jam sibuk (11.00–13.00 & 17.00–19.00) $\ge$ 70%.
     3. Tingkat penyelesaian pesanan $\ge$ 80%.
   - **Alur Jam 20.01**: Begitu pengguna membuka aplikasi dan mengonfirmasi waktu online $\ge$ 9 jam, estimasi Bonus Hub langsung diakui dan ditambahkan ke **Total Pendapatan Hari Ini**.
   - Jika waktu online < 9 jam, bonus hari itu otomatis Rp0.
4. **Pencairan Saldo ShopeePay**:
   - Bonus Hub cair resmi dari Shopee ke ShopeePay antara pukul 22.00 – 22.30.
   - Saat closing malam, pengguna mengonfirmasi apakah saldo bonus sudah mendarat di ShopeePay.

---

### Modul C: Monitoring Saldo On-Beat & GoPay Merchant
1. **Layar Cepat On-Beat (Snapshot Progres)**:
   Pengguna menginput 4 instrumen sekaligus:
   - Total Pesanan Selesai, Pendapatan Hub, Bonus Hub, Tip Pelanggan di Shopee.
   - Saldo ShopeePay saat ini.
   - Saldo GoPay saat ini.
   - Saldo GoPay Merchant saat ini.
2. **Aturan Bisnis GoPay Merchant**:
   - Saldo dari QRIS pelanggan tertahan di GoPay Merchant.
   - Auto-settle ke GoPay setiap 1 jam jika saldo $\ge$ Rp10.000.
   - Penambahan saldo GoPay/GoPay Merchant di jalan otomatis diakui sebagai **Pendapatan QRIS / Tip Tanpa Nama** (pengeluaran pribadi di jalan diisolasi lewat BCA).

---

### Modul D: Quick Action Lapangan
1. **Quick Parkir**:
   - 1-tap potong Cash Rp1.000.
   - Koordinat GPS dan waktu tercatat otomatis.
   - Nama resto opsional (bisa diisi cepat atau ditinggalkan kosong).
2. **Quick Bensin**:
   - Pilihan cepat 1-tap:
     - `[SPBU]` (Patokan Rp10.000/liter)
     - `[Warung Madura]` (Patokan Rp12.000/botol)
   - Input wajib: Nominal uang (Rp) dan Akun sumber bayar (Cash/BCA).
   - Volume liter otomatis dihitung oleh sistem.
3. **Voice Memo (Intercom Helm)**:
   - Tombol rekam cepat audio (.m4a/.mp3) di client mobile.
   - Menyimpan rekaman suara, waktu kejadian, dan lokasi GPS.
   - Tujuan: Menangkap nomor pesanan COD, tip berlebih dadakan, atau lokasi celah parkir gratis tanpa harus mengetik saat motoran.

---

### Modul E: Pengelolaan Utang, Paylater, & Teman
1. **Platform Utang**:
   - SPayLater, SeaBank Pinjam, GoPay Pinjam.
   - Mencatat pokok, biaya bunga/admin/provisi, total kewajiban, tenor, jatuh tempo, dan jadwal angsuran.
2. **Pelunasan Utang Teman (Multi-split Ledger)**:
   - Contoh kasus: Teman berutang Rp249.000, membayar Rp248.000.
   - 1 Form Cepat:
     - Input uang diterima: Rp248.000 (masuk ke BCA/Cash).
     - Checklist: `[Diikhlaskan / Dianggap Lunas]`.
     - Efek sistem: Utang teman ditutup LUNAS, dan selisih Rp1.000 otomatis dicatat ke akun beban **Sedekah / Bantuan Pribadi**.

---

### Modul F: Rekonsiliasi, Closing, & Deteksi Selisih
1. **Independensi Tanggal Kerja**:
   - Tanggal kerja tidak terikat jam sistem (closing jam 03.00 subuh tetap dicatat untuk tanggal kerja hari sebelumnya).
2. **Kalkulator Denominasi Uang Fisik**:
   - Koin: Rp100, Rp200, Rp500, Rp1.000.
   - Kertas: Rp1.000, Rp2.000, Rp5.000, Rp10.000, Rp20.000, Rp50.000, Rp100.000.
   - Sistem menjumlahkan total fisik dan membandingkannya dengan saldo buku Cash.
3. **Aturan Selisih & Status Tanggal**:
   - Status: `Draft / Needs Reconciliation` dan `Final`.
   - Jika ada selisih saldo digital/cash $\ne$ Rp0:
     - Tanggal tersebut **TIDAK BISA difinalkan**.
     - Pengguna **TETAP BISA membuka dan mencatat tanggal kerja baru** keesokan harinya.
     - Aplikasi mobile menampilkan **Banner Pengingat Mencolok** di beranda: *"Tanggal X belum difinalisasi (Selisih RpY). Klik untuk investigasi"*.
   - Status hanya berubah menjadi `Final` saat selisih mencapai Rp0 setelah transaksi terlupa dimasukkan.
