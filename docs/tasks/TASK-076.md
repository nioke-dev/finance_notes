# TASK-076: Setup Autentikasi Laravel Sanctum & Migrasi `personal_access_tokens`

**Modul:** 0. Autentikasi & Keamanan Dasar  
**Fokus Tunggal:** Setup Sanctum Token Driver untuk Komunikasi Aman Mobile Flutter ke Backend  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Mengonfigurasi Laravel Sanctum untuk mengamankan seluruh API endpoint v1 yang diakses oleh aplikasi mobile Flutter dan session web:
1. **Konfigurasi Sanctum:**
   - Memastikan paket `laravel/sanctum` terpasang dan konfigurasi `config/sanctum.php` terdaftar.
   - Migrasi tabel `personal_access_tokens`.
   - Menambahkan trait `Laravel\Sanctum\HasApiTokens` pada model `User`.
2. **Karakteristik Token Driver:**
   - Single-user application (User ID 1 = Akun Driver Pribadi).
   - Token memiliki masa berlaku permanen / long-lived untuk device driver di motor, dengan kemampuan revocable jika HP hilang.

---

## 2. File-File yang Disentuh / Dibuat
1. `database/migrations/xxxx_xx_xx_000000_create_personal_access_tokens_table.php` — Migrasi token Sanctum.
2. `app/Models/User.php` — Penambahan trait `HasApiTokens`.
3. `tests/Feature/Auth/SanctumSetupTest.php` — Automated test verifikasi Sanctum token generation.

---

## 3. Spesifikasi Anatomi Kode (`app/Models/User.php`)
```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Perintah migrasi Sanctum berjalan sukses tanpa benturan skema.
2. Model `User` berhasil menghasilkan API Token melalui metode `$user->createToken('mobile-device')->plainTextToken`.
3. Automated test memverifikasi pembentukan dan validasi token Sanctum 100%.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Auth/SanctumSetupTest.php`
- **Metode Uji:**
  1. `test_user_can_generate_and_authenticate_via_sanctum_token()`:
     - Buat user baru.
     - Generate token via Sanctum.
     - Panggil rute `auth:sanctum` dengan header `Authorization: Bearer {token}` $\rightarrow$ Verifikasi HTTP 200.
