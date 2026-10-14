# MASTER CONTEXT — APLIKASI KEUANGAN PRIBADI & SHOPEEFOOD DRIVER
**Tanggal pemindahan konteks:** 12 Oktober 2026  
**Tujuan:** Memindahkan konteks proyek dari ChatGPT ke Gemini agar perencanaan, PRD, task backlog, dan implementasi melalui Google Anti-Gravity tetap konsisten.

## 1. Instruksi untuk Gemini

Bertindaklah sebagai arsitek produk, analis kebutuhan, penjaga aturan bisnis, dan reviewer implementasi. Google Anti-Gravity adalah AI coding agent yang akan mengerjakan kode. Jangan langsung mulai coding atau menganggap PRD sudah final.

Cara bekerja:
1. Pahami seluruh konteks di dokumen ini.
2. Jangan menjadi yes-man. Tunjukkan kontradiksi, risiko, asumsi yang belum terbukti, dan celah logika.
3. Bedakan keputusan menjadi **DIKUNCI**, **USULAN**, dan **BELUM DIPUTUSKAN**.
4. Jangan mengarang detail yang belum disepakati. Bertanya hanya jika jawabannya benar-benar diperlukan.
5. Utamakan efisiensi. Pengguna sangat tidak suka testing manual berulang dan prompt-fix-test tanpa akhir.
6. Jangan menjanjikan bebas bug/akurasi 100%. Gunakan spesifikasi eksplisit, automated tests, pemeriksaan statis, pemeriksaan integrasi, dan bukti hasil tes.
7. Jangan mengandalkan riwayat chat sebagai satu-satunya sumber kebenaran. Simpan keputusan dalam dokumen di repository.
8. Jawab dalam bahasa Indonesia yang natural, santai, dan langsung ke inti. Pengguna biasanya menyukai jawaban ringkas, bukan penjelasan panjang berulang.
9. Jangan membuat PRD final sebelum pengguna sempat meninjau dan mengoreksinya.
10. Jangan meminta pengguna menguji semua skenario manual jika dapat dijadikan automated test.

Peran:
- **Gemini:** merancang kebutuhan, PRD, spesifikasi, task backlog, menganalisis celah, dan meninjau kode/laporan yang dibagikan.
- **Google Anti-Gravity:** mengimplementasikan kode, menjalankan tes/tooling yang tersedia, memperbaiki kegagalan, memperbarui status task dan dokumentasi.
- **Pengguna:** pemilik produk dan pemberi keputusan untuk hal yang memerlukan preferensi atau otorisasi.

Jangan mengasumsikan Gemini otomatis bisa melihat repository atau percakapan Anti-Gravity. Jika tidak ada koneksi langsung, pertukarkan dokumen, diff/patch, log, laporan tes, atau file proyek.

## 2. Tujuan dan stack proyek

Membuat aplikasi **keuangan pribadi + pencatatan aktivitas dan pendapatan ShopeeFood driver**, untuk penggunaan pribadi, dengan web dan mobile.

- Backend/web: Laravel.
- Mobile: Flutter.
- MySQL pernah digunakan di proyek lain, tetapi database final untuk aplikasi ini masih perlu dikunci.
- Pengguna punya/menyiapkan hosting dan domain.
- Coding agent: Google Anti-Gravity, bukan Codex.
- CodingPakeAI.com: untuk sekarang **tidak disarankan dipakai**; jangan menambah layanan/lapisan konversi kebutuhan sebelum terbukti perlu.

Prioritas:
1. Akurasi transaksi dan saldo.
2. Input cepat, target sekitar 5–10 detik untuk transaksi sederhana di mobile.
3. Rekonsiliasi transparan dan audit trail.
4. Konsistensi logika web dan mobile.
5. UI rapi dan konsisten, bukan harus premium atau penuh animasi.
6. Struktur data cukup baik agar AI/OCR/voice bisa ditambahkan nanti.

Pengguna ingin MVP cepat, tetapi memahami voice/OCR/AI bukan target realistis untuk MVP pertama.

## 3. Workflow yang disarankan

1. Bahas kebutuhan dan selesaikan ambiguitas.
2. Susun beberapa file Markdown, bukan satu prompt raksasa.
3. Pengguna meninjau dan mengoreksi.
4. Buat task backlog kecil, berurutan, dengan dependensi dan kriteria selesai.
5. Masukkan instruksi permanen, misalnya `AGENTS.md`.
6. Anti-Gravity mengerjakan task secara bertahap.
7. Setiap task: perubahan kecil → automated tests/tooling → perbaiki kegagalan → laporkan bukti → baru tandai selesai.
8. Pengguna hanya melakukan pemeriksaan manual di checkpoint penting.
9. Jika ada kontradiksi/kebutuhan belum jelas, agent harus berhenti pada bagian terdampak dan bertanya atau mencatat keputusan; jangan mengarang.
10. Perubahan kebutuhan harus memperbarui dokumen dan task terkait.

Dokumen yang direncanakan (belum dibuat):
- `README.md`
- `AGENTS.md`
- `docs/PRD.md`
- `docs/BUSINESS-RULES.md`
- `docs/DATA-MODEL.md`
- `docs/API-CONTRACTS.md`
- `docs/UI-DESIGN-SYSTEM.md`
- `docs/TASK-BACKLOG.md`
- `docs/DECISIONS.md`
- `docs/ACCEPTANCE-TESTS.md`
- `docs/IMPLEMENTATION-PLAN.md`

Aturan kualitas Anti-Gravity:
- Membaca `AGENTS.md` dan dokumen relevan sebelum mulai.
- Jangan mengubah aturan bisnis terkunci tanpa izin.
- Jangan menandai task selesai bila tes wajib gagal atau belum dijalankan.
- Laporkan tes yang benar-benar dijalankan; jangan mengklaim pengujian yang tidak dilakukan.
- Catat file yang diubah, keputusan, hasil pemeriksaan, dan risiko tersisa.
- Jangan menghapus atau mengubah transaksi tanpa audit trail.
- Jangan membangun fitur di atas task fondasi yang gagal.
- Gunakan komponen UI reusable.
- Jika dokumen konflik, jangan menebak.

## 4. UI dan desain

Pengguna tidak mengejar desain premium, tetapi ingin antarmuka bersih, konsisten, dan tidak tampak seperti HTML bawaan tanpa styling.

Perhatian khusus:
- Input teks mentah.
- Pemilih tanggal `input type=date`.
- Upload file `input type=file`.
- Dropdown, tombol, textarea, error states, dan komponen form yang tampil tidak konsisten.

Aturan UI yang diusulkan:
- Satu design system: warna, tipografi, spacing, border, radius, tinggi komponen, focus state, dan pesan error.
- Jangan meninggalkan komponen form dalam gaya browser default jika tidak cocok dengan sistem desain.
- Komponen reusable untuk input, select, textarea, date picker, upload, tombol, dialog, dan notifikasi.
- State relevan: default, hover, focus, disabled, loading, error, success.
- Web responsif; Flutter mengikuti pola visual/interaksi yang konsisten.
- Lakukan visual inspection pada halaman penting.
- Beberapa detail input tanggal/file dikendalikan browser/OS; bila perlu gunakan komponen custom yang aksesibel dan stabil, bukan CSS rapuh.
- Task UI belum selesai hanya karena fungsi berjalan; konsistensi visual adalah kriteria penerimaan.

## 5. Rutinitas driver

- Pengguna bekerja sebagai driver ShopeeFood di Malang.
- Biasanya mulai sekitar pukul 10.00, selesai sekitar pukul 20.00–22.00, kadang 23.00 atau lewat tengah malam.
- Jam ramai yang diperhatikan: 11.00–13.00 dan 17.00–19.00.
- Kadang pindah lokasi atau offline sementara karena area sepi; offline tidak boleh otomatis dianggap tidak produktif.
- Sering mengisi bensin setelah kerja.
- Malam hari setelah kembali ke kos, pengguna menghitung uang fisik, mengecek saldo bank/e-wallet, menginput pendapatan, lalu menutup tanggal kerja.
- Kadang closing dilakukan pukul 03.00–06.00 hari berikutnya; data tetap terkait tanggal kerja yang dipilih.
- Tidak ingin fitur sesi kerja, tracking istirahat, target pendapatan harian, atau perhitungan jam kerja sebagai fitur MVP.

## 6. Pendapatan ShopeeFood

Hanya ada **tiga komponen pendapatan ShopeeFood**:
1. Pendapatan Hub.
2. Bonus Hub.
3. Tip pelanggan.

Jangan menambah kategori “pendapatan order” atau kategori keempat tanpa klarifikasi. Pengguna pernah menyebut “pendapatan reguler”, tetapi perlu dikonfirmasi apakah maksudnya Pendapatan Hub.

Total pendapatan ShopeeFood = Pendapatan Hub + Bonus Hub + Tip pelanggan. Jumlah order selesai dicatat terpisah.

Tarif bonus yang disampaikan:
- Order 1–15: Rp0/order.
- 16–20: Rp1.000/order.
- 21–25: Rp1.500/order.
- 26+: Rp2.000/order.

Contoh 31 order = Rp24.500, dengan asumsi bertingkat/incremental (5×1.000 + 5×1.500 + 6×2.000). Sebelum mengaktifkan kalkulasi otomatis, konfirmasikan bahwa skema benar-benar bertingkat dan tentukan apakah angka Bonus Hub manual tetap menjadi sumber utama atau dibandingkan dengan estimasi otomatis.

## 7. Update progres dan closing

- Pengguna bisa memasukkan progres beberapa kali untuk satu tanggal kerja, misalnya pukul 20.00 dan 23.00.
- Setiap update menyimpan timestamp dan riwayat, bukan menimpa snapshot sebelumnya diam-diam.
- Update berisi jumlah order selesai, Pendapatan Hub, Bonus Hub, tip pelanggan.
- Update bukan closing. Tidak ada target harian.
- Tidak ada tracking sesi/jam kerja.
- Tanggal kerja terpisah dari waktu input/closing. Closing untuk tanggal 9 Oktober yang dilakukan tanggal 10 Oktober pukul 05.00 tetap milik tanggal kerja 9 Oktober.
- Simpan tanggal kerja, waktu kejadian aktual bila diketahui, waktu input, dan waktu koreksi terpisah.
- Jika transaksi masa lalu ditemukan tetapi waktu aslinya tidak diketahui, pengguna pernah memilih pukul 23.59 sebagai placeholder pada tanggal kerja terkait. Tandai sebagai placeholder/perkiraan, bukan waktu faktual, dan simpan waktu input serta alasan.
- Status closing: Draft/Needs Reconciliation dan Final.
- Default: jangan izinkan Final jika selisih saldo belum dijelaskan. Pengecualian untuk transaksi pending/bukti belum ada harus menjadi keputusan eksplisit.

## 8. Akun awal

1. BCA
2. SeaBank
3. ShopeePay
4. GoPay
5. GoPay Merchant
6. Cash/tunai
7. e-money/KTM
8. Flazz

Akun dan kategori dinamis. Semua akun memiliki saldo awal manual; saldo awal bukan pendapatan. Setelah saldo awal difinalisasi, koreksi wajib memiliki audit trail (nilai lama/baru, tanggal, alasan). Cash adalah akun penuh yang saldonya berubah oleh transaksi tunai. Pengguna ingin melihat saldo tiap akun, total digital, uang tunai, dan total dana tanpa membuka aplikasi satu per satu.

## 9. Ledger dan transaksi

Jenis konseptual:

**Uang masuk:** pendapatan, transfer masuk, pencairan pinjaman, pembayaran dari orang/pihak ketiga.

**Uang keluar:** pengeluaran, transfer keluar, pembayaran utang, pengguna menutup kekurangan pembayaran orang lain.

**Khusus:** saldo awal, penarikan tunai, koreksi/rekonsiliasi.

Semua transaksi bisa dikoreksi, tetapi tidak boleh diubah/dihapus diam-diam:
- Simpan histori/original atau versi sebelumnya.
- Catat efek koreksi.
- Hitung ulang saldo.
- Transaksi yang diinput terlambat membutuhkan alasan.
- Koreksi menyimpan waktu, perubahan, dan alasan.

### Transfer sendiri
Transfer antar-akun sendiri bukan pendapatan/pengeluaran kecuali biaya admin.
Contoh BCA → ShopeePay Rp100.000 dengan admin Rp1.000:
- BCA −Rp101.000.
- ShopeePay +Rp100.000.
- Biaya admin Rp1.000 adalah pengeluaran.

Top-up: sumber −Rp50.000, tujuan +Rp49.500, biaya Rp500. Input boleh satu form dengan efek ledger terhubung.

Penarikan tunai:
- Tujuan selalu Cash.
- Sumber dipilih.
- Admin opsional.
- Bukan pendapatan; admin adalah pengeluaran.

Riwayat transfer harus menampilkan nominal, biaya, akun asal/tujuan, dan efek yang terhubung.

## 10. GoPay Merchant dan QRIS

- Pelanggan bisa membayar via QRIS ke GoPay Merchant.
- Saldo bisa disettle ke GoPay secara berkala.
- Jangan menebak/menyimulasikan waktu settlement otomatis.
- Setiap malam pengguna mengecek saldo aktual GoPay Merchant dan GoPay.
- Catat saldo aktual dan transfer yang benar-benar diinput; jangan mengarang settlement.
- Reimbursement parkir dapat masuk secara acak dan tidak selalu bisa dikaitkan ke pelanggan/order.
- OCR, statement parsing, dan AI klasifikasi bukan MVP.

## 11. Pecahan uang tunai

Denominasi:
- Koin Rp100, Rp200, Rp500, Rp1.000.
- Uang kertas Rp1.000, Rp2.000, Rp5.000, Rp10.000, Rp20.000, Rp50.000, Rp100.000.

Saat closing, pengguna menghitung kuantitas tiap denominasi. Aplikasi menghitung total cash fisik dan membandingkannya dengan saldo Cash yang diharapkan.
- Snapshot denominasi diperbarui saat hitung fisik berikutnya.
- Pagi hari dapat menampilkan denominasi terbanyak sebagai informasi.
- Jangan melacak pecahan yang dipakai dalam tiap transaksi.
- Pengeluaran tunai Rp15.000 hanya mengurangi akun Cash Rp15.000; tak perlu menentukan pecahan.

## 12. Rekonsiliasi cash dan digital

Ini fitur penting.

1. Setiap malam, pengguna memasukkan saldo aktual tiap akun digital dan menghitung cash fisik dari denominasi.
2. Bandingkan saldo aktual dengan saldo ledger.
3. Tampilkan selisih tepat sampai Rp1; tidak boleh ada pembulatan/toleransi tersembunyi.
4. Pengguna menyelidiki mutasi rekening/e-wallet.
5. Pengguna bisa menambahkan transaksi terlupa, mengoreksi nominal, memperbaiki duplikasi, atau memperbaiki saldo awal dengan audit trail.
6. Saldo dihitung ulang setelah koreksi.
7. Tampilkan alasan/penjelasan dan status selisih.
8. Jangan memaksa pengguna mengarang penyebab hanya untuk closing.

Contoh: saldo buku BCA Rp1.900.000, aktual Rp2.000.000 → selisih +Rp100.000. Aplikasi harus membantu investigasi, bukan otomatis membuat adjustment +Rp100.000 yang menyembunyikan transaksi sebenarnya.

Penyebab dapat berupa transaksi masuk/keluar terlupa, nominal salah, duplikasi, saldo awal salah, transaksi pending/perbedaan waktu posting, atau akun salah. Contoh QRIS: pembelian aktual Rp13.150 tetapi dicatat Rp13.000; koreksi transaksi asli dengan histori, bukan menambal saldo tanpa konteks.

Pengguna ingin mengekspor cash flow aplikasi dan membandingkannya dengan statement bank/e-wallet, mungkin dengan LLM eksternal. AI masa depan boleh menyarankan pencocokan tetapi manusia menyetujui perubahan sebelum transaksi ditulis. CSV/XLSX patut dipertimbangkan untuk analisis terstruktur dan PDF untuk baca/cetak; format final belum diputuskan.

Default: tidak boleh final jika selisih belum selesai. Pengguna sangat tidak menyukai adjustment generik yang menyembunyikan transaksi asli. Cash aktual tidak boleh dipakai untuk menyimpulkan pengeluaran parkir yang terlupa.

## 13. Parkir

- Pengeluaran kecil yang penting, biasanya Rp1.000 dan sering tunai.
- Reimbursement pelanggan dapat berupa tunai, QRIS/GoPay Merchant, atau tip ShopeeFood, dan waktunya/identitasnya mungkin tidak diketahui.
- Jangan mewajibkan pelacakan reimbursement per pelanggan/order.
- Input cepat; default akun Cash, nominal default mungkin Rp1.000, nama restoran opsional.
- Timestamp otomatis.
- GPS pernah dipertimbangkan tetapi status MVP belum diputuskan.
- Ringkasan boleh membandingkan biaya parkir dengan reimbursement yang teridentifikasi, tetapi jangan menganggap semua reimbursement diketahui.
- Selisih parkir vs reimbursement bukan otomatis selisih cash.

## 14. COD

Catat nominal yang seharusnya dibayar, nominal aktual diterima, dan surplus/shortfall.
Contoh COD Rp15.125, pelanggan memberi Rp16.000 → surplus Rp875.
Surplus bukan pendapatan order; dicatat terpisah. Input cepat diutamakan.

## 15. Bensin dan kendaraan

- Nominal dibayar wajib.
- Akun sumber wajib.
- Liter opsional.
- Jenis/konteks tempat beli (SPBU/warung) opsional.
- Jangan hard-code harga per liter.
- Konteks lama: SPBU sekitar Rp10.000/liter, warung sekitar Rp12.000/liter; perkiraan saja.
- Alokasi bensin pernah sekitar Rp40.000/hari, tetapi tidak ada target aplikasi.
- Masa depan: km/liter, biaya/km, jarak harian berdasarkan odometer.
- Odometer start/end pernah dipertimbangkan, tetapi pengguna tidak mau tracking sesi kerja/jam kerja. Apakah odometer dibutuhkan untuk analitik jarak masih belum diputuskan. Jangan membuat tracking sesi kerja.

## 16. Utang, paylater, dan orang

Platform yang perlu didukung:
- SPayLater.
- SeaBank Pinjam.
- Opsi potensial: Shopee Pinjam, GoPay Pinjam, SeaBank Pinjam, SPayLater.
- Pengguna pernah mengatakan utang aktif saat ini hanya dua; platform mana belum dipastikan secara eksplisit. Jangan mengasumsikan semua platform aktif.
- Tidak perlu tracking limit kredit.

Field utang: platform/pemberi pinjaman, transaksi/item asal, tanggal transaksi, pokok, biaya bunga/admin/provisi (untuk MVP bisa disatukan sebagai biaya pinjaman), total kewajiban, tenor, cicilan bulanan, tanggal mulai/akhir, jatuh tempo per bulan, jadwal, pembayaran aktual, promo, status.

Pinjaman Rp600.000 dengan biaya Rp100.000 dan dana bersih diterima Rp500.000 bisa dicatat sebagai field terpisah. Jangan mengasumsikan nominal bersih jika tidak diinput.

### SPayLater
- Pengguna sering membayar bulan berikutnya untuk mempertahankan promo 0% bila berlaku.
- Catat barang, tanggal, toko, nominal, proyeksi tagihan per bulan.
- Contoh tagihan Rp250.000 dibayar Rp200.000 karena promo; kewajiban, pembayaran, dan manfaat promo harus terlihat terpisah.
- Promo pembayaran utang bukan pengeluaran normal; catat sebagai manfaat/pengurangan kewajiban secara eksplisit.
- Pelunasan awal menandai periode terkait lunas tanpa menghapus jadwal periode berikutnya.

### Pinjaman
- SeaBank Pinjam biasanya 3 cicilan dan menurut pengguna bunganya lebih rendah.
- GoPay Pinjam dapat memotong admin/provisi, misalnya pinjam Rp600.000 tetapi dana bersih diterima sekitar Rp500.000 lebih sedikit. Catat biaya pinjaman dan dana bersih terpisah.

### Utang orang/teman
- Orang dinamis, misalnya Rofik, Arno, Rafsan.
- Contoh Rofik memakai SPayLater pengguna untuk membeli ponsel; utangnya mencakup kewajiban yang terkait, termasuk biaya.
- Jika utang Rp249.000 tetapi membayar Rp248.000, pengguna bisa memilih menanggung Rp1.000 sebagai bantuan/sedekah.
- Catat dua efek: penerimaan Rp248.000 ke akun terpilih dan kekurangan Rp1.000 ditanggung pengguna dari akun terpilih.
- Jangan otomatis men-netting utang menjadi Rp247.000.
- Tanyakan akun sumber saat pengguna menanggung kekurangan, lalu perbarui status sesuai aturan yang disepakati.
- Utang pribadi dan utang teman terpisah, tetapi semua arus uang tercatat di ledger.

## 17. Kategori, laporan, analitik

- Kategori pengeluaran dinamis: bensin, makanan, parkir, minuman, self-reward, perawatan kendaraan, admin, pajak, dll.
- Pengguna dapat menambah/mengubah kategori.
- Subkategori bisa dipertimbangkan nanti.
- Laporan cash flow bulanan bisa diekspor/dicetak untuk dibandingkan dengan mutasi akun.
- Format ekspor belum diputuskan; rekomendasi awal CSV/XLSX untuk analisis dan PDF untuk baca/cetak.
- Web: konfigurasi akun/kategori, utang, laporan, riwayat, audit, analitik.
- Mobile: input cepat, saldo, update driver, closing.
- Analitik masa depan: jarak/hari, efisiensi bensin, pendapatan per jam/km, pendapatan bersih, biaya bensin/km, km/liter, order/jam, frekuensi transfer, distribusi saldo akun. Jangan menghitung pendapatan/jam tanpa data durasi yang valid.

## 18. Fitur AI masa depan, bukan MVP

- Voice note/speech-to-text.
- OCR struk dan screenshot transaksi.
- Agent/LLM yang menyarankan pencatatan.
- Import/klasifikasi statement bank/e-wallet.
- Pencocokan transaksi internal dan eksternal.
- Analisis pola cashback setelah data beberapa bulan.
- Integrasi bank/e-wallet otomatis jika layak.

Prinsip:
- Gunakan model ledger yang sama; tidak perlu database AI terpisah.
- AI menyarankan, bukan diam-diam mengubah transaksi.
- Persetujuan manusia sebelum mutasi data keuangan.
- Jangan membuat pola cashback dari sedikit contoh; catat aktual dahulu dan analisis setelah cukup bukti.
- API/struktur data harus mendukung otomasi masa depan.

## 19. Kriteria kualitas inti

1. Akurasi hingga Rp1, tanpa pembulatan/toleransi tersembunyi.
2. Efek transfer antar-akun terhubung.
3. Koreksi dan transaksi terlambat punya audit trail.
4. Saldo awal bukan pendapatan.
5. Transfer sendiri bukan pendapatan/pengeluaran kecuali biaya.
6. Update progres driver berbeda dari closing.
7. Tanggal kerja berbeda dari waktu input/closing.
8. Rekonsiliasi tidak mengarang penyebab selisih.
9. Saldo aktual dan saldo ledger ditampilkan terpisah.
10. Closing Final diblokir jika ada selisih belum selesai, kecuali pengecualian eksplisit kelak disetujui.
11. Web dan mobile menggunakan aturan backend yang sama.
12. Tidak ada komponen UI mentah yang tertinggal.
13. Automated tests untuk logika inti; tidak mengandalkan input manual berulang.
14. Agent melaporkan bukti tes yang benar-benar dijalankan.
15. Jangan mengklaim sesuatu sudah dibuat/diuji/diverifikasi jika belum.

## 20. Hal yang belum diputuskan

Jangan diam-diam menjadikannya keputusan final:
1. Database final dan arsitektur deployment.
2. Konfirmasi tarif bonus bertingkat dan apakah bonus otomatis hanya estimasi atau sumber utama.
3. Format ekspor: CSV, XLSX, PDF atau kombinasi.
4. Apakah GPS parkir termasuk MVP.
5. Apakah odometer diperlukan untuk analitik jarak/efisiensi.
6. Mekanisme pengecualian closing saat selisih belum bisa dijelaskan; default adalah tidak boleh Final.
7. Kemampuan aktual Anti-Gravity menjalankan browser automation, visual checks, dan test suite.
8. Dua utang aktif yang benar-benar berjalan saat ini.
9. Scope MVP dan urutan rilis.
10. Detail API, autentikasi, backup, keamanan, sinkronisasi web/mobile.
11. Pemetaan istilah “pendapatan reguler” ke “Pendapatan Hub”.

## 21. Langkah berikut

1. Gemini merangkum pemahaman proyek singkat dan menyebut maksimal 5 risiko/ambiguitas penting.
2. Susun PRD per modul, minta pengguna mengoreksi.
3. Setelah disetujui, buat spesifikasi teknis dan task backlog dengan ID unik, dependensi, acceptance criteria, dan automated tests.
4. Buat task fondasi kecil untuk menguji workflow Anti-Gravity.
5. Buat `AGENTS.md` dan dokumen sumber acuan di repository.
6. Mulai implementasi setelah dokumen inti disetujui.

Jangan kembali bertanya apakah pengguna ingin memakai CodingPakeAI.com. Rekomendasi saat ini adalah tidak memakai layanan tambahan dulu.

## 22. Prompt pembuka untuk Gemini

> Kamu akan menjadi arsitek produk dan penjaga konsistensi logika untuk aplikasi dalam dokumen konteks ini. Baca seluruh dokumen. Jangan langsung membuat kode atau menganggap semua hal sudah final. Berikan ringkasan pemahaman singkat, maksimal 5 risiko/ambiguitas paling penting, lalu sarankan langkah berikut yang paling efisien. Jangan menanyakan hal yang sudah dijawab. Utamakan automated tests agar aku tidak perlu menguji setiap skenario secara manual. Google Anti-Gravity adalah coding agent yang akan mengimplementasikan aplikasi berdasarkan dokumen yang kita setujui. Kita ingin PRD dan task backlog rinci, dapat diperiksa, dan konsisten dengan aturan bisnis.
