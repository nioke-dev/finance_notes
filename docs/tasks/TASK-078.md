# TASK-078: Halaman Web Login Driver Blade Tailwind (`GET /login` & `POST /login`)

**Modul:** 0. Autentikasi & Keamanan Dasar  
**Fokus Tunggal:** Controller Web Auth, Form Login Blade Dark Theme, & Session Authenticate  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun antarmuka login berbasis web untuk driver saat membuka dashboard super app di laptop/browser:
1. **Rute & Controller Web Auth:**
   - `GET /login`: Menampilkan halaman login Blade.
   - `POST /login`: Memvalidasi email & password, mengautentikasi session web via `Auth::attempt`, meregenerasi session ID (`$request->session()->regenerate()`), dan me-redirect ke `/dashboard`.
   - `POST /logout`: Menghapus session (`Auth::logout`) dan me-redirect ke `/login`.
2. **Desain Antarmuka Login Blade (`auth/login.blade.php`):**
   - Tema Dark Slate (`bg-slate-900`, card `bg-slate-800/90`, border `border-slate-700`).
   - Form input ramah visual (tidak memakai styling mentah bawaan browser).
   - Menampilkan alert merah Tailwind jika terjadi kesalahan password.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Controllers/Web/AuthWebController.php` — Controller session login web.
2. `resources/views/auth/login.blade.php` — Tampilan form login Blade Tailwind.
3. `routes/web.php` — Pendaftaran route login/logout web.
4. `tests/Feature/Web/WebLoginTest.php` — Automated test form login web.

---

## 3. Spesifikasi Anatomi Kode (`app/Http/Controllers/Web/AuthWebController.php`)
```php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthWebController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Rute `GET /login` merender view `auth.login` dengan status 200.
2. Kredensial benar me-redirect ke `/dashboard` dan mengautentikasi session.
3. Kredensial salah mengembalikan session error dan tetap di `/login`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/WebLoginTest.php`
- **Metode Uji:**
  1. `test_login_screen_can_be_rendered()`:
     - Akses `GET /login` $\rightarrow$ Pastikan HTTP 200.
  2. `test_user_can_authenticate_using_the_login_screen()`:
     - Kirim payload kredensial valid $\rightarrow$ Pastikan redirect ke `/dashboard` dan `Auth::check() == true`.
