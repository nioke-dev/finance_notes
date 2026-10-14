# TASK-034: Form Request & API Endpoint Quick Bensin (`POST /api/v1/quick/fuel`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** HTTP Endpoint Quick Bensin dengan Pemilihan Akun Bayar Cepat  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk menangani aksi Quick Bensin dari aplikasi mobile.
- **Route:** `POST /api/v1/quick/fuel`
- **Request Payload:**
```json
{
  "work_day_id": 1,
  "account_id": 1,
  "vendor_type": "spbu",
  "amount": 30000
}
```
- Validasi ketat:
  - `work_day_id` wajib eksis di tabel `work_days`.
  - `account_id` wajib eksis di tabel `accounts`.
  - `vendor_type` wajib salah satu dari: `spbu` atau `madura`.
  - `amount` wajib integer minimal Rp1.000 (`min:1000`).
- Mengembalikan response JSON standar `201 Created` dengan estimasi volume liter dan nama akun yang terpotong.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/QuickFuelRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/QuickFuelController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/quick/fuel`.
4. `tests/Feature/Api/QuickFuelApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/QuickFuelRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickFuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'vendor_type' => ['required', 'string', 'in:spbu,madura'],
            'amount' => ['required', 'integer', 'min:1000'],
        ];
    }
}
```

### B. Controller Action (`QuickFuelController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuickFuelRequest;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\QuickFuelService;
use Illuminate\Http\JsonResponse;

class QuickFuelController extends Controller
{
    public function store(
        QuickFuelRequest $request,
        QuickFuelService $fuelService
    ): JsonResponse {
        $validated = $request->validated();

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $account = Account::findOrFail($validated['account_id']);

        $transaction = $fuelService->recordFuel(
            workDay: $workDay,
            sourceAccount: $account,
            vendorType: $validated['vendor_type'],
            amount: $validated['amount']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pengeluaran bensin berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $validated['amount'],
                'vendor_type' => $validated['vendor_type'],
                'estimated_volume' => $transaction->metadata['estimated_volume'],
                'account_name' => $account->name,
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/quick/fuel` merespons `201 Created` saat payload valid.
2. Request dengan nominal bensin di bawah Rp1.000 menghasilkan error `422 Unprocessable Entity`.
3. Request dengan vendor selain `spbu` atau `madura` menghasilkan error `422 Unprocessable Entity`.
4. Response JSON mengembalikan `estimated_volume` dan `account_name`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/QuickFuelApiTest.php`
- **Metode Uji:**
  1. `test_quick_fuel_api_succeeds_and_returns_volume_data()`:
     - Input Rp30k SPBU via BCA $\rightarrow$ status 201, volume 3.0.
  2. `test_quick_fuel_api_rejects_invalid_vendor_type()`:
     - Kirim vendor `'pertamax'` $\rightarrow$ status 422.
  3. `test_quick_fuel_api_rejects_amount_under_one_thousand()`:
     - Kirim amount Rp500 $\rightarrow$ status 422.
