# TASK-021: Migrasi Tabel Konfigurasi Dinamis Bonus Hub (`shopee_bonus_schemes`) & Model

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** Penyimpanan Dinamis Skema Tingkatan Bonus Hub agar Tidak Ter-hardcode  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun tabel database `shopee_bonus_schemes` dan Model Eloquent `ShopeeBonusScheme`.
Sesuai instruksi pengguna di `BUSINESS-RULES.md`:
- Skema bonus ShopeeFood **WAJIB DINAMIS** (bisa berubah sewaktu-waktu atau event berakhir).
- Menyimpan ambang batas order (*min_orders*, *max_orders*) dan tarif bonus per order (*rate_per_order*).
- Memiliki flag `is_active` sehingga skema lama dapat dinonaktifkan tanpa merusak riwayat masa lalu.

Default Data (Seeder/Inisialisasi):
- Tier 1: Order 1 s/d 15 $\rightarrow$ Tarif Rp0
- Tier 2: Order 16 s/d 20 $\rightarrow$ Tarif Rp1.000 / order
- Tier 3: Order 21 s/d 25 $\rightarrow$ Tarif Rp1.500 / order
- Tier 4: Order 26 ke atas (`max_orders = null`) $\rightarrow$ Tarif Rp2.000 / order

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000006_create_shopee_bonus_schemes_table.php` — File migrasi tabel.
2. `app/Models/ShopeeBonusScheme.php` — Model Eloquent.
3. `tests/Unit/Models/ShopeeBonusSchemeTest.php` — Automated test verifikasi skema tabel dan query scope.

---

## 3. Spesifikasi Skema Database (`shopee_bonus_schemes`)
```php
Schema::create('shopee_bonus_schemes', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('tier_level'); // 1, 2, 3, 4
    $table->unsignedInteger('min_orders'); // Contoh: 16
    $table->unsignedInteger('max_orders')->nullable(); // Contoh: 20 (null untuk tak hingga)
    $table->bigInteger('rate_per_order'); // Contoh: 1000 (Rupiah utuh presisi Rp1)
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    // Indexes
    $table->index('is_active');
    $table->index('tier_level');
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` sukses membuat tabel `shopee_bonus_schemes`.
2. Model memiliki query scope `scopeActive(Builder $query)`.
3. Kolom `rate_per_order` bertipe integer/bigint presisi Rupiah.
4. Perintah rollback berhasil menghapus tabel secara bersih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Unit/Models/ShopeeBonusSchemeTest.php`
- **Metode Uji:**
  1. `test_shopee_bonus_schemes_table_has_all_required_columns()`:
     - Memverifikasi kolom `id`, `tier_level`, `min_orders`, `max_orders`, `rate_per_order`, `is_active`.
  2. `test_scope_active_filters_only_active_schemes()`:
     - Memastikan query `ShopeeBonusScheme::active()->get()` hanya mengambil tier yang aktif.
