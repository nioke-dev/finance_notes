# TASK-020: Form Request & API Endpoint Pembalikan Transaksi (`POST /api/v1/transactions/{id}/reverse`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** HTTP Endpoint Reversal Transaksi Keuangan dengan Validasi Alasan  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk membatalkan/mengoreksi transaksi keuangan dari UI Web Super App atau Mobile.
- **Route:** `POST /api/v1/transactions/{id}/reverse`
- **Request Payload:**
```json
{
  "reason": "Salah input nominal parkir, seharusnya Rp1.000 bukan Rp10.000"
}
```
- Validasi ketat:
  - Transaksi dengan ID `{id}` wajib ada di tabel `transactions`.
  - Field `reason` wajib ada, bertipe string, dan minimal memiliki panjang 5 karakter (`min:5`).
- Mengembalikan response JSON standar `200 OK` dengan metadata transaksi pembalik yang berhasil dibuat.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/ReverseTransactionRequest.php` — Form request validasi pembatalan.
2. `app/Http/Controllers/Api/TransactionController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint reversal.
4. `tests/Feature/Api/ReverseTransactionApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/ReverseTransactionRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReverseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
```

### B. Controller Action (`TransactionController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReverseTransactionRequest;
use App\Models\Transaction;
use App\Services\ReversalService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function reverse(
        int $id,
        ReverseTransactionRequest $request,
        ReversalService $reversalService
    ): JsonResponse {
        $transaction = Transaction::findOrFail($id);

        $reversal = $reversalService->reverseTransaction(
            originalTransaction: $transaction,
            reason: $request->validated('reason')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi berhasil dibatalkan melalui transaksi pembalik.',
            'data' => [
                'original_transaction_id' => $transaction->id,
                'reversal_transaction_id' => $reversal->id,
                'reason' => $request->validated('reason'),
            ],
        ], 200);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/transactions/{id}/reverse` merespons `200 OK` saat pembatalan sukses.
2. Request tanpa field `reason` atau dengan `reason < 5 karakter` menghasilkan error `422 Unprocessable Entity`.
3. Percobaan membatalkan transaksi yang sudah pernah di-reverse menghasilkan error `422 Unprocessable Entity`.
4. Response JSON mengembalikan ID transaksi awal dan ID transaksi pembalik.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/ReverseTransactionApiTest.php`
- **Metode Uji:**
  1. `test_successful_transaction_reversal_returns_200_ok()`:
     - Mengirim request valid, memastikan status 200 dan response memuat data pembalikan.
  2. `test_reversal_without_reason_returns_422_validation_error()`:
     - Mengirim payload tanpa `reason`, memastikan validasi 422.
  3. `test_reversing_non_existent_transaction_returns_404_not_found()`:
     - Mengakses ID 999999, memastikan response 404.
