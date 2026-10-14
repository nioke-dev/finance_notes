# TASK-005: Seeder Master Akun Default Keuangan Driver (`DefaultAccountsSeeder`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Inisialisasi Akun Standar Finansial Driver & Kategori Beban/Pendapatan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membuat file Database Seeder Laravel `DefaultAccountsSeeder` yang bertugas mengisi akun-akun dasar operasional milik driver sesuai dokumen `BUSINESS-RULES.md` dan `PRD.md`.
Akun yang wajib dibuat meliputi:
- **Akun Bank & E-Wallet**:
  1. `BCA` (`type = bank`) — Rekening belanja pribadi & beli bensin/minum.
  2. `SeaBank` (`type = bank`) — Rekening tabungan & pembayaran pinjaman.
  3. `ShopeePay` (`type = ewallet`) — Penampung pendapatan ShopeeFood.
  4. `GoPay` (`type = ewallet`) — E-wallet operasional & hasil settlement QRIS.
  5. `GoPay Merchant` (`type = ewallet`) — Penampung QRIS pelanggan lapangan.
  6. `Cash` (`type = cash`) — Uang fisik di kantong/dompet.
  7. `Flazz / e-money` (`type = ewallet`) — Saldo kartu tol/transportasi.
- **Akun Kategori Pendapatan (Income)**:
  8. `Pendapatan Hub ShopeeFood` (`type = income`)
  9. `Bonus Hub ShopeeFood` (`type = income`)
  10. `Tip Pelanggan ShopeeFood` (`type = income`)
  11. `Tip Cash / Surplus COD` (`type = income`)
  12. `Pendapatan QRIS / Tip Non-Tunai` (`type = income`)
- **Akun Kategori Beban (Expense)**:
  13. `Beban Bensin Kendaraan` (`type = expense`)
  14. `Beban Parkir` (`type = expense`)
  15. `Beban Makanan & Minuman Driver` (`type = expense`)
  16. `Beban Biaya Admin Transfer` (`type = expense`)
  17. `Beban Sedekah & Bantuan Pribadi` (`type = expense`)

Seluruh akun diinisialisasi dengan saldo `0`. Saldo awal sebenarnya akan dimasukkan melalui `AccountService` di task berikutnya.

---

## 2. File-File yang Disentuh / Dibuat
1. `database/seeders/DefaultAccountsSeeder.php` — Database seeder class.
2. `database/seeders/DatabaseSeeder.php` — Mendaftarkan `DefaultAccountsSeeder`.
3. `tests/Feature/Seeders/DefaultAccountsSeederTest.php` — Automated test verifikasi kelengkapan akun.

---

## 3. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan db:seed --class=DefaultAccountsSeeder` berhasil tanpa duplikasi (menggunakan `updateOrCreate` berbasis `name`).
2. Tepat 17 akun dasar terdaftar dengan tipe enum yang tepat.
3. Seluruh akun default memiliki status `is_active = true`.

---

## 4. Automated Tests Wajib
- **File:** `tests/Feature/Seeders/DefaultAccountsSeederTest.php`
- **Metode Uji:**
  1. `test_seeder_creates_all_essential_driver_accounts()`:
     - Memverifikasi keberadaan 'BCA', 'ShopeePay', 'GoPay', 'GoPay Merchant', dan 'Cash'.
  2. `test_seeder_can_run_multiple_times_without_duplicate_errors()`:
     - Menjalankan seeder dua kali berturut-turut dan memastikan jumlah akun tetap sama (idempotent).
