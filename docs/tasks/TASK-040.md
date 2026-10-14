# TASK-040: Form Request & API Endpoint Pencatatan COD Lapangan (`POST /api/v1/quick/cod`)

**Modul:** 3. Quick Action Lapangan  
**Fokus Tunggal:** HTTP Endpoint Pencatatan Pesanan COD & Deteksi Kelebihan Uang Tunai  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk mencatat transaksi COD dari aplikasi mobile secara cepat di depan rumah pelanggan.
- **Route:** `POST /api/v1/quick/cod`
- **Request Payload:**
```json
{
  "work_day_id": 1,
  "order_number": "SPF-100829",
  "bill_amount": 15125,
  "received_amount": 16000
}
```
- Validasi ketat:
  - `work_day_id` wajib eksis di tabel `work_days`.
  - `bill_amount` integer minimal Rp1.
  - `received_amount` integer minimal sama dengan `bill_amount` (`gte:bill_amount`).
  - `order_number` opsional string maksimal 50 karakter.
- Mengembalikan response JSON standar `201 Created` dengan rincian surplus tip yang berhasil dihitung sistem.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/StoreCodOrderRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/CodOrderController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/quick/cod`.
4. `tests/Feature/Api/CodOrderApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/StoreCodOrderRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCodOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'order_number' => ['nullable', 'string', 'max:50'],
            'bill_amount' => ['required', 'integer', 'min:1'],
            'received_amount' => ['required', 'integer', 'gte:bill_amount'],
        ];
    }
}
```

### B. Controller Action (`CodOrderController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCodOrderRequest;
use App\Models\WorkDay;
use App\Services\CodOrderService;
use Illuminate\Http\JsonResponse;

class CodOrderController extends Controller
{
    public function store(
        StoreCodOrderRequest $request,
        CodOrderService $codService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        $transaction = $codService->recordCodOrder(
            workDay: $workDay,
            billAmount: $validated['bill_amount'],
            receivedAmount: $validated['received_amount'],
            orderNumber: $validated['order_number'] ?? null
        );

        $surplus = $validated['received_amount'] - $validated['bill_amount'];

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi COD berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'bill_amount' => $validated['bill_amount'],
                'received_amount' => $validated['received_amount'],
                'surplus_tip' => $surplus,
                'order_number' => $validated['order_number'] ?? null,
                'recorded_at' => $transaction->transaction_time->toISOString(),
            ],
        ], 201);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/quick/cod` merespons `201 Created` saat payload valid.
2. Request dengan `received_amount < bill_amount` menghasilkan error validasi `422 Unprocessable Entity`.
3. Response mengembalikan nilai `surplus_tip` secara transparan.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/CodOrderApiTest.php`
- **Metode Uji:**
  1. `test_can_record_cod_with_surplus_via_api()`:
     - Tagihan 15.125, terima 16.000 $\rightarrow$ status 201, `surplus_tip = 875`.
  2. `test_cod_fails_if_received_less_than_bill_via_api()`:
     - Tagihan 20.000, terima 15.000 $\rightarrow$ status 422.
