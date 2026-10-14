# TASK-008: Form Request & API Endpoint Inisialisasi Saldo Awal (`POST /api/v1/accounts/initial-balance`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** HTTP Endpoint Setup Saldo Awal Akun Pertama Kali  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun HTTP Request Validation class dan Controller Endpoint API untuk menerima input saldo awal akun dari antarmuka Web atau Mobile pada saat setup awal aplikasi:
- **Route:** `POST /api/v1/accounts/initial-balance`
- **Request Payload:**
```json
{
  "account_id": 1,
  "amount": 2500000
}
```
- Validasi ketat:
  - `account_id` wajib ada dan eksis di tabel `accounts`.
  - `amount` wajib integer dan bernilai $\ge 0$.
- Mengembalikan response JSON standar `200 OK` dengan status sukses dan saldo ter-update.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/SetInitialBalanceRequest.php` — Class validasi request.
2. `app/Http/Controllers/Api/AccountController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint API v1.
4. `tests/Feature/Api/SetInitialBalanceApiTest.php` — Automated test endpoint HTTP.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/SetInitialBalanceRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetInitialBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

### B. Route & Controller Action
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::post('/accounts/initial-balance', [AccountController::class, 'setInitialBalance']);
});
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/accounts/initial-balance` merespons `200 OK` saat request payload valid.
2. Request dengan `amount < 0` merespons `422 Unprocessable Entity`.
3. Request dengan `account_id` yang tidak terdaftar merespons `422 Unprocessable Entity`.
4. Response JSON memuat struktur baku:
```json
{
  "status": "success",
  "message": "Saldo awal berhasil diatur.",
  "data": {
    "account_id": 1,
    "name": "BCA",
    "current_balance": 2500000
  }
}
```

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/SetInitialBalanceApiTest.php`
- **Metode Uji:**
  1. `test_can_set_initial_balance_via_api()`:
     - Mengirim payload valid dan memastikan response status 200 dan database ter-update.
  2. `test_cannot_set_negative_initial_balance()`:
     - Mengirim payload amount negatif dan memastikan validation error 422.
  3. `test_cannot_set_initial_balance_for_non_existent_account()`:
     - Mengirim payload dengan ID akun 999999 dan memastikan validation error 422.
