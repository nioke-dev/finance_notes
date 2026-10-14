# TASK-027: Form Request & API Endpoint Simpan Snapshot On-Beat All-in-One (`POST /api/v1/driver/snapshot`)

**Modul:** 2. Operasional ShopeeFood & Lapangan Engine  
**Fokus Tunggal:** HTTP Endpoint Input Cepat Progres di Jalan Menampung 4 Instrumen  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk menerima input snapshot progres lapangan saat driver istirahat on-beat di jalan (misalnya jam 21.00 malam di sela-sela menunggu orderan).
- **Route:** `POST /api/v1/driver/snapshot`
- **Request Payload:**
```json
{
  "work_day_id": 1,
  "completed_orders": 22,
  "hub_income": 176000,
  "hub_bonus": 8000,
  "customer_tips": 15000,
  "shopeepay_balance": 520000,
  "gopay_balance": 185000,
  "gopay_merchant_balance": 8500
}
```
- Validasi ketat:
  - `work_day_id` wajib ada dan eksis di tabel `work_days`.
  - Seluruh field angka wajib integer dan bernilai $\ge 0$.
- Mengembalikan response JSON standar `201 Created` dengan rincian total pendapatan driver saat ini dan total holding digital.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/StoreDriverSnapshotRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/DriverSnapshotController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/driver/snapshot`.
4. `tests/Feature/Api/DriverSnapshotApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/StoreDriverSnapshotRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'completed_orders' => ['required', 'integer', 'min:0'],
            'hub_income' => ['required', 'integer', 'min:0'],
            'hub_bonus' => ['required', 'integer', 'min:0'],
            'customer_tips' => ['required', 'integer', 'min:0'],
            'shopeepay_balance' => ['required', 'integer', 'min:0'],
            'gopay_balance' => ['required', 'integer', 'min:0'],
            'gopay_merchant_balance' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

### B. Controller Action (`DriverSnapshotController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDriverSnapshotRequest;
use App\Models\DriverSnapshot;
use App\Models\WorkDay;
use Illuminate\Http\JsonResponse;

class DriverSnapshotController extends Controller
{
    public function store(StoreDriverSnapshotRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $snapshot = DriverSnapshot::create([
            'work_day_id' => $workDay->id,
            'recorded_at' => now(),
            'completed_orders' => $validated['completed_orders'],
            'hub_income' => $validated['hub_income'],
            'hub_bonus' => $validated['hub_bonus'],
            'customer_tips' => $validated['customer_tips'],
            'shopeepay_balance' => $validated['shopeepay_balance'],
            'gopay_balance' => $validated['gopay_balance'],
            'gopay_merchant_balance' => $validated['gopay_merchant_balance'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Snapshot progres driver berhasil disimpan.',
            'data' => [
                'snapshot_id' => $snapshot->id,
                'total_driver_income' => $snapshot->total_driver_income,
                'total_digital_holding' => $snapshot->total_digital_holding,
                'merchant_settlement_status' => $snapshot->merchant_settlement_status,
                'recorded_at' => $snapshot->recorded_at->toISOString(),
            ],
        ], 201);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/driver/snapshot` merespons `201 Created` saat payload valid.
2. Request dengan nominal negatif menghasilkan error `422 Unprocessable Entity`.
3. Response JSON memuat atribut komputasi `total_driver_income` dan `total_digital_holding`.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/DriverSnapshotApiTest.php`
- **Metode Uji:**
  1. `test_can_store_valid_driver_snapshot_via_api()`:
     - Mengirim payload valid dan memastikan response 201 beserta nilai agregat yang tepat.
  2. `test_snapshot_fails_validation_if_any_balance_is_negative()`:
     - Mengirim nilai saldo negatif dan memastikan validation error 422.
