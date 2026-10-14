# TASK-074: Migrasi Database Tabel Kategori Transaksi Dinamis (`categories`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** Migrasi Skema Tabel Master Kategori Dinamis  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun file migrasi database untuk tabel `categories` guna mengelola klasifikasi pemasukan dan pengeluaran secara fleksibel sesuai `PRD.md` (tidak hardcoded di kode):
1. **Struktur Kolom Tabel `categories`:**
   - `id`: Big Increments (Primary Key).
   - `name`: String (misal: "Bensin", "Parkir", "Makan Minum", "Sedekah", "Operasional Shopee", "Cicilan PayLater").
   - `type`: Enum (`income`, `expense`, `transfer`).
   - `is_system`: Boolean (default `false`) — Menandai kategori bawaan sistem yang tidak boleh dihapus driver.
   - `icon`: String nullable (nama ikon Lucide / Heroicons).
   - `color`: String nullable (HEX color token Tailwind, misal `#10B981`, `#EE4D2D`).
   - `created_at` & `updated_at`: Timestamp.

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000008_create_categories_table.php` — File migrasi tabel categories.
2. `tests/Feature/Database/CategoriesMigrationTest.php` — Automated test integritas skema tabel.

---

## 3. Spesifikasi Anatomi Kode (`create_categories_table.php`)
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->boolean('is_system')->default(false);
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah `php artisan migrate` mengeksekusi migrasi tanpa error.
2. Kolom `name`, `type`, dan `is_system` memiliki tipe data dan batasan yang tepat.
3. Automated test memverifikasi eksistensi dan tipe kolom pada tabel `categories`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Database/CategoriesMigrationTest.php`
- **Metode Uji:**
  1. `test_categories_table_has_expected_columns()`:
     - Jalankan migrasi.
     - Verifikasi `Schema::hasColumns('categories', ['id', 'name', 'type', 'is_system'])` mengembalikan `true`.
