# TASK-079: Middleware Proteksi Session Web & Grouping Rute Dashboard

**Modul:** 0. Autentikasi & Keamanan Dasar  
**Fokus Tunggal:** Pengamanan Seluruh Rute Web Super App dengan Middleware `auth`  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Menerapkan pengamanan ketat pada seluruh rute aplikasi web keuangan agar data saldo, mutasi, utang, dan analitik driver tidak dapat diakses tanpa login:
1. **Grouping Route Web:**
   - Memasukkan seluruh rute internal (`/dashboard`, `/closing/*`, `/analytics/*`, `/debts`, `/export/*`) ke dalam grup middleware `auth`.
   - Tamu (*guest*) yang mencoba mengakses URL internal secara langsung otomatis di-redirect ke `/login`.
2. **Redirect After Login:**
   - Menyimpan intended URL sehingga setelah login berhasil, pengguna langsung diarahkan kembali ke halaman yang awalnya ingin dibuka (misal langsung ke halaman closing).
3. **Penyusunan File Rute Bersih:**
   - Mengorganisasi `routes/web.php` secara modular dengan komentar penjelas yang rapi.

---

## 2. File-File yang Disentuh / Dibuat
1. `routes/web.php` — Penataan grup route middleware auth.
2. `tests/Feature/Web/WebAuthProtectionTest.php` — Automated test proteksi seluruh rute web.

---

## 3. Spesifikasi Anatomi Kode (`routes/web.php`)
```php
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ClosingWebController;
use App\Http\Controllers\Web\DriverAnalyticsController;
use App\Http\Controllers\Web\FuelAnalyticsController;
use App\Http\Controllers\Web\DebtWebController;
use App\Http\Controllers\Web\ExportReportController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');
    
    Route::get('/', fn() => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/closing/{id}', [ClosingWebController::class, 'show'])->name('closing.show');
    Route::get('/analytics/driver', [DriverAnalyticsController::class, 'index'])->name('analytics.driver');
    Route::get('/analytics/fuel', [FuelAnalyticsController::class, 'index'])->name('analytics.fuel');
    Route::get('/debts', [DebtWebController::class, 'index'])->name('debts.index');
    Route::get('/export/transactions/csv', [ExportReportController::class, 'exportCsv'])->name('export.csv');
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Tamu yang mengakses `/dashboard` atau `/analytics/driver` di-redirect ke `/login` dengan status HTTP 302.
2. Pengguna yang telah terautentikasi dapat mengakses rute-rute tersebut dengan status HTTP 200.
3. Automated test memverifikasi proteksi seluruh rute.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Web/WebAuthProtectionTest.php`
- **Metode Uji:**
  1. `test_unauthenticated_users_are_redirected_to_login()`:
     - Akses `GET /dashboard` tanpa login $\rightarrow$ Pastikan redirect ke `/login`.
  2. `test_authenticated_users_can_access_dashboard()`:
     - Login sebagai user valid $\rightarrow$ Akses `GET /dashboard` $\rightarrow$ Pastikan status 200.
