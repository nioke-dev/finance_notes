# TASK-077: Form Request & API Controller Login Driver (`POST /api/v1/auth/login`)

**Modul:** 0. Autentikasi & Keamanan Dasar  
**Fokus Tunggal:** Endpoint Login Mobile & Issuing Sanctum Bearer Token  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun endpoint API autentikasi untuk aplikasi mobile Flutter di `POST /api/v1/auth/login`:
1. **Form Request Validation (`LoginDriverRequest`):**
   - `email`: Required, email format.
   - `password`: Required, string, min 8 karakter.
   - `device_name`: Required, string (misal: "Redmi Note 12 - Helm Intercom").
2. **Controller Logic (`AuthController@login`):**
   - Memverifikasi kredensial driver dengan `Hash::check`.
   - Jika salah: Mengembalikan HTTP 422 dengan pesan *"Kredensial driver tidak cocok"*.
   - Jika benar: Mencabut token lama dari device yang sama, menerbitkan token baru, dan mengembalikan response JSON:
     - `token`: Plain text Sanctum token.
     - `user`: Data id, name, email.
3. **Pemisahan File:** Validasi ketat dipisah ke kelas Form Request mandiri, Controller hanya menangani flow respons.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/Auth/LoginDriverRequest.php` — Form Request validasi input login.
2. `app/Http/Controllers/Api/V1/AuthController.php` — API Controller login & logout.
3. `routes/api.php` — Pendaftaran route `POST /api/v1/auth/login` dan `POST /api/v1/auth/logout`.
4. `tests/Feature/Auth/DriverLoginApiTest.php` — Automated test login & issuing token.

---

## 3. Spesifikasi Anatomi Kode (`LoginDriverRequest.php`)
```php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/auth/login` memvalidasi input via `LoginDriverRequest`.
2. Kredensial benar mengembalikan status 200 dengan token bearer plain text.
3. Kredensial salah mengembalikan HTTP 422.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Auth/DriverLoginApiTest.php`
- **Metode Uji:**
  1. `test_driver_can_login_with_valid_credentials_and_receive_token()`:
     - Kirim payload login valid $\rightarrow$ Pastikan HTTP 200 dan key `token` ada di response JSON.
  2. `test_driver_cannot_login_with_invalid_password()`:
     - Kirim password salah $\rightarrow$ Pastikan HTTP 422.
