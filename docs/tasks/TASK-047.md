# TASK-047: Form Request & API Endpoint Pelunasan Utang Teman (`POST /api/v1/debts/friend-payment`)

**Modul:** 4. Pengelolaan Utang, PayLater, & Teman  
**Fokus Tunggal:** HTTP Endpoint Pelunasan Utang Teman dengan Opsi Checklist Auto-Sedekah  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk menerima input pelunasan utang dari teman melalui aplikasi Web atau Mobile.
- **Route:** `POST /api/v1/debts/friend-payment`
- **Request Payload:**
```json
{
  "work_day_id": 1,
  "debt_id": 3,
  "account_id": 1,
  "received_amount": 248000,
  "forgive_remainder": true
}
```
- Validasi ketat:
  - `work_day_id` wajib eksis di tabel `work_days`.
  - `debt_id` wajib eksis di tabel `debts`.
  - `account_id` wajib eksis di tabel `accounts` (rekening bank/kas penerima transfer).
  - `received_amount` wajib integer minimal Rp1.
  - `forgive_remainder` boolean wajib (default `false`).
- Mengembalikan response JSON standar `200 OK` dengan status lunas dan nominal sedekah yang tercatat.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/FriendDebtPaymentRequest.php` — Form request validasi.
2. `app/Http/Controllers/Api/FriendDebtController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/debts/friend-payment`.
4. `tests/Feature/Api/FriendDebtPaymentApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/FriendDebtPaymentRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FriendDebtPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'debt_id' => ['required', 'integer', 'exists:debts,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'received_amount' => ['required', 'integer', 'min:1'],
            'forgive_remainder' => ['required', 'boolean'],
        ];
    }
}
```

### B. Controller Action (`FriendDebtController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FriendDebtPaymentRequest;
use App\Models\Account;
use App\Models\Debt;
use App\Models\WorkDay;
use App\Services\FriendDebtSettlementService;
use Illuminate\Http\JsonResponse;

class FriendDebtController extends Controller
{
    public function pay(
        FriendDebtPaymentRequest $request,
        FriendDebtSettlementService $settlementService
    ): JsonResponse {
        $validated = $request->validated();

        $workDay = WorkDay::findOrFail($validated['work_day_id']);
        $debt = Debt::findOrFail($validated['debt_id']);
        $account = Account::findOrFail($validated['account_id']);

        $transaction = $settlementService->settleFriendDebt(
            workDay: $workDay,
            debt: $debt,
            receivingAccount: $account,
            receivedAmount: $validated['received_amount'],
            forgiveRemainder: $validated['forgive_remainder']
        );

        $remainder = max(0, $debt->total_obligation - $validated['received_amount']);

        return response()->json([
            'status' => 'success',
            'message' => 'Pembayaran utang teman berhasil dicatat.',
            'data' => [
                'transaction_id' => $transaction->id,
                'borrower_name' => $debt->borrower_name,
                'received_amount' => $validated['received_amount'],
                'remainder' => $remainder,
                'is_paid_off' => $debt->fresh()->isPaidOff(),
                'charity_recorded' => $validated['forgive_remainder'] ? $remainder : 0,
            ],
        ], 200);
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/debts/friend-payment` merespons `200 OK` saat pembayaran sukses.
2. Jika `forgive_remainder = true`, response mengembalikan `is_paid_off = true` dan nilai `charity_recorded`.
3. Validasi menolak nominal pembayaran $\le 0$ atau request tanpa ID utang yang valid.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/FriendDebtPaymentApiTest.php`
- **Metode Uji:**
  1. `test_can_settle_friend_debt_with_forgiveness_via_api()`:
     - Bayar 248k untuk utang 249k diikhlaskan $\rightarrow$ Status 200, is_paid_off = true, charity_recorded = 1000.
  2. `test_friend_debt_payment_fails_for_non_existent_debt()`:
     - Mengirim ID utang 999999 $\rightarrow$ Status 422.
