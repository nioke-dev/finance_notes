# TASK-018: Form Request & API Endpoint Transfer Antar-Akun (`POST /api/v1/transfers`)

**Modul:** 1. Fondasi Backend & Database Engine  
**Fokus Tunggal:** HTTP Endpoint Transfer Antar-Aset Sendiri + Validasi Multi-Field  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk menangani transfer antar-rekening/dompet dari frontend (Web maupun Mobile).
- **Route:** `POST /api/v1/transfers`
- **Request Payload:**
```json
{
  "work_day_id": 1,
  "from_account_id": 1,
  "to_account_id": 2,
  "amount": 100000,
  "admin_fee": 1000,
  "transaction_time": "2026-10-14 11:30:00",
  "note": "Top up ShopeePay dari BCA"
}
```
- Validasi ketat:
  - `work_day_id` wajib ada dan eksis di tabel `work_days`.
  - `from_account_id` wajib eksis di tabel `accounts`.
  - `to_account_id` wajib eksis dan berbeda dari `from_account_id` (`different:from_account_id`).
  - `amount` integer $\ge 1$.
  - `admin_fee` integer opsional $\ge 0$ (default 0).

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/TransferRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/TransferController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/transfers`.
4. `tests/Feature/Api/TransferApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/TransferRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'integer', 'min:1'],
            'admin_fee' => ['nullable', 'integer', 'min:0'],
            'transaction_time' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

### B. Controller Action (`TransferController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Models\Account;
use App\Models\WorkDay;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class TransferController extends Controller
{
    public function store(TransferRequest $request, TransferService $transferService): JsonResponse
    {
        $validated = $request->validated();

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $fromAccount = Account::findOrFail($validated['from_account_id']);
        $toAccount = Account::findOrFail($validated['to_account_id']);

        $transaction = $transferService->transfer(
            workDay: $workDay,
            sourceAccount: $fromAccount,
            destinationAccount: $toAccount,
            amount: $validated['amount'],
            adminFee: $validated['admin_fee'] ?? 0,
            transactionTime: isset($validated['transaction_time']) ? Carbon::parse($validated['transaction_time']) : now(),
            note: $validated['note'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Transfer berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'from_account' => $fromAccount->name,
                'to_account' => $toAccount->name,
                'amount' => $validated['amount'],
                'admin_fee' => $validated['admin_fee'] ?? 0,
            ],
        ], 201);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/transfers` merespons `201 Created` saat transfer sukses.
2. Request dengan `from_account_id` dan `to_account_id` yang sama menghasilkan `422 Unprocessable Entity`.
3. Request dengan nominal amount 0 menghasilkan `422 Unprocessable Entity`.
4. Response JSON mengembalikan rincian akun dan nominal secara transparan.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/TransferApiTest.php`
- **Metode Uji:**
  1. `test_successful_transfer_returns_201_created_and_updates_balances()`:
     - Mengirim payload transfer valid, memastikan status 201 dan saldo akun database ter-update.
  2. `test_transfer_to_same_account_returns_422_validation_error()`:
     - Mengirim ID akun sumber dan tujuan sama, memastikan validasi 422.
  3. `test_transfer_with_negative_admin_fee_returns_422()`:
     - Menguji penolakan biaya admin negatif.
