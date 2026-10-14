# TASK-032: Form Request & API Endpoint Quick Parkir (`POST /api/v1/quick/parking`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** HTTP Endpoint Quick Parkir Berkecepatan Tinggi (< 200ms)  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk menangani aksi Quick Parkir dari tombol 1-tap di aplikasi mobile.
- **Route:** `POST /api/v1/quick/parking`
- **Request Payload:**
```json
{
  "work_day_id": 1,
  "amount": 1000,
  "restaurant_name": "Mie Gacoan Sutoyo",
  "latitude": -7.966620,
  "longitude": 112.632632
}
```
- Validasi ringkas & toleran:
  - `work_day_id` wajib ada dan eksis di tabel `work_days`.
  - `amount` opsional integer $\ge 0$ (default otomatis `1000`).
  - `restaurant_name` opsional string maksimal 150 karakter.
  - `latitude` dan `longitude` opsional numerik.
- Respons cepat dalam format JSON standar `201 Created` agar HP driver tidak mengalami lag saat di jalan.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/QuickParkingRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/QuickParkingController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/quick/parking`.
4. `tests/Feature/Api/QuickParkingApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/QuickParkingRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickParkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'restaurant_name' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
```

### B. Controller Action (`QuickParkingController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuickParkingRequest;
use App\Models\WorkDay;
use App\Services\QuickParkingService;
use Illuminate\Http\JsonResponse;

class QuickParkingController extends Controller
{
    public function store(
        QuickParkingRequest $request,
        QuickParkingService $parkingService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $transaction = $parkingService->recordParking(
            workDay: $workDay,
            amount: $validated['amount'] ?? QuickParkingService::DEFAULT_PARKING_FEE,
            restaurantName: $validated['restaurant_name'] ?? null,
            latitude: $validated['latitude'] ?? null,
            longitude: $validated['longitude'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Biaya parkir berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $validated['amount'] ?? QuickParkingService::DEFAULT_PARKING_FEE,
                'restaurant_name' => $validated['restaurant_name'] ?? null,
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/quick/parking` merespons `201 Created` hanya dengan mengirim `work_day_id`.
2. Jika parameter `amount` tidak disertakan, nominal otomatis default ke Rp1.000.
3. Response JSON mengembalikan ID transaksi dan waktu pencatatan.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/QuickParkingApiTest.php`
- **Metode Uji:**
  1. `test_quick_parking_api_succeeds_with_minimal_payload()`:
     - Hanya mengirim `work_day_id` $\rightarrow$ status 201, amount Rp1.000.
  2. `test_quick_parking_api_saves_full_details_when_provided()`:
     - Mengirim nama resto dan koordinat GPS $\rightarrow$ data tersimpan lengkap.
  3. `test_quick_parking_fails_for_non_existent_work_day()`:
     - Mengirim ID 999999 $\rightarrow$ validation error 422.
