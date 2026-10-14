# ATURAN BISNIS RESMI (BUSINESS-RULES.md)
**Status:** DIKUNCI (Hasil Klarifikasi Pengguna)

Dokumen ini memuat seluruh aturan bisnis baku untuk aplikasi Keuangan Pribadi & ShopeeFood Driver.

---

## 1. Akun & Inisialisasi Saldo
1. **Daftar Akun Utama**:
   - BCA (Bank)
   - SeaBank (Bank)
   - ShopeePay (E-Wallet Penampung ShopeeFood)
   - GoPay (E-Wallet Pribadi)
   - GoPay Merchant (Penampung QRIS Lapangan)
   - Cash / Tunai (Akun Fisik)
   - e-money / Flazz / KTM (Opsional)
2. **Inisialisasi Saldo Awal**:
   - Saldo awal diinput secara manual pada saat setup pertama kali.
   - Saldo awal adalah **modal awal**, bukan pendapatan.
   - Koreksi saldo awal wajib memiliki audit trail (alasan, waktu, nilai lama/baru).

---

## 2. Operasional ShopeeFood & Lapangan (On-Beat)
1. **Form Snapshot Progres Cepat**:
   Saat di jalan, pengguna dapat mengecek total pendapatan detik itu juga dengan menginput:
   - Order Selesai (Jumlah)
   - Pendapatan Hub (Rp)
   - Bonus Hub (Rp)
   - Tip Pelanggan di Shopee (Rp)
   - Saldo ShopeePay saat ini
   - Saldo GoPay saat ini
   - Saldo GoPay Merchant saat ini
2. **GoPay Merchant & QRIS Pelanggan**:
   - Pelanggan membayar via QRIS langsung masuk ke GoPay Merchant.
   - Penambahan saldo GoPay/GoPay Merchant saat on-beat otomatis diakui sebagai **Pendapatan QRIS / Tip**, tanpa perlu mengisi nama resto/pelanggan.
   - GoPay Merchant auto-settle ke GoPay setiap 1 jam jika saldo $\ge$ Rp10.000.
3. **Mekanisme Perhitungan Bonus Hub (Dinamis)**:
   - **Tingkatan (Tiering)**:
     - Order 1–15: Rp0 / order
     - Order 16–20: Rp1.000 / order (maks Rp5.000)
     - Order 21–25: Rp1.500 / order (maks Rp7.500)
     - Order 26+: Rp2.000 / order
   - **Kriteria Kelayakan (Cut-off Jam 20.00)**:
     - Hanya pesanan yang masuk rentang **10.00 – 20.00** yang eligible dihitung.
     - Wajib memenuhi performa: Total waktu online $\ge$ 9 jam (dikonfirmasi pengguna via pop-up jam 20.00 ke atas).
     - Jika waktu online $\ge$ 9 jam, bonus langsung diakui sebagai estimasi pendapatan hari itu.
     - Jika waktu online < 9 jam, bonus hari itu = Rp0.
   - **Pencairan Resmi**:
     - Bonus cair ke ShopeePay antara pukul 22.00 – 22.30.

---

## 3. Quick Action Lapangan
1. **Quick Parkir**:
   - 1-tap potong Cash Rp1.000.
   - Mencatat waktu dan koordinat GPS secara otomatis.
   - Kolom nama resto bersifat opsional.
2. **Bensin**:
   - Pilihan cepat 1-tap:
     - `[SPBU]` (estimasi patokan Rp10.000/liter)
     - `[Warung Madura]` (estimasi patokan Rp12.000/botol)
   - Input wajib: Nominal bayar (Rp) & Akun sumber (Cash/BCA).
   - Volume (liter) otomatis dihitung sistem, pengguna tidak perlu mengetik liter.
3. **Voice Memo (Intercom Helm)**:
   - Tombol rekam cepat audio (.m4a/.mp3) di mobile client.
   - Menyimpan rekaman suara, timestamp, dan lokasi GPS.
   - Berfungsi sebagai catatan pembantu audit/investigasi selisih uang di malam hari.

---

## 4. Pelunasan Utang Teman & Talangan
- 1 form cepat pelunasan utang:
  - Nominal diterima: (misal Rp248.000 ke akun BCA/Cash).
  - Opsi kekurangan: `[Diikhlaskan / Dianggap Lunas]` atau `[Tetap Jadi Sisa Utang]`.
  - Jika diikhlaskan, sisa utang (misal Rp1.000) otomatis dibebankan ke pos **Sedekah / Bantuan Pribadi** dan utang teman ditutup lunas.

---

## 5. Rekonsiliasi & Closing Harian
1. **Prinsip Tanggal Kerja**:
   - Tanggal kerja independen dari jam closing fisik (closing jam 03.00 subuh tetap milik tanggal kerja hari sebelumnya).
2. **Penanganan Selisih**:
   - Jika saldo fisik/e-wallet dan catatan buku belum cocok (ada selisih), tanggal kerja tetap berstatus `Needs Reconciliation`.
   - Pengguna **tetap diizinkan membuka tanggal kerja baru** tanpa ada status rumit yang menghambat.
   - Aplikasi mobile menampilkan kartu pengingat mencolok di beranda untuk menyelesaikan rekonsiliasi tanggal yang belum klop.
   - Tanggal hanya bisa di-set ke status `Final` jika selisih = Rp0 (semua transaksi terlupa sudah dimasukkan).
