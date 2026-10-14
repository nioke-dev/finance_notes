# TASK-053: Form Request & API Finalisasi Closing Hari Kerja (`POST /api/v1/reconciliation/finalize-day`)

**Modul:** 5. Rekonsiliasi, Denominasi, & Closing Engine  
**Fokus Tunggal:** HTTP Endpoint Penutupan Hari Kerja dengan Penolakan Ketat saat Selisih $\ne$ 0  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Membangun validasi request dan endpoint API untuk menangani penutupan resmi (*final closing*) hari kerja dari Web Super App atau Mobile.
- **Route:** `POST /api/v1/reconciliation/finalize-day`
- **Request Payload:**
```json
{
  "work_day_id": 1,
  "digital_balances": [
    { "account_id": 1, "actual_balance": 2500000 },
    { "account_id": 2, "actual_balance": 750000 },
    { "account_id": 3, "actual_balance": 650000 },
    { "account_id": 4, "actual_balance": 250000 }
  ],
  "closing_notes": "Semua mutasi sudah cocok dan fisik dihitung pas."
}
```
- **Aturan Respon:**
  - Jika $\sum |\text{Selisih}| == 0$: Mengembalikan HTTP `200 OK` dengan pesan sukses dan status hari kerja berubah ke `final`.
  - Jika $\sum |\text{Selisih}| > 0$: Mengembalikan HTTP `422 Unprocessable Entity` dengan rincian akun-akun yang mengalami selisih uang. Hari kerja tetap berstatus `needs_reconciliation`.

---

## 2. File-File yang Disentuh / Dibuat
1. `app/Http/Requests/FinalizeWorkDayRequest.php` — Form request validasi closing.
2. `app/Http/Controllers/Api/ReconciliationController.php` — Controller endpoint API.
3. `routes/api.php` — Mendaftarkan route endpoint `POST /api/v1/reconciliation/finalize-day`.
4. `tests/Feature/Api/FinalizeWorkDayApiTest.php` — Automated test HTTP endpoint.

---

## 3. Spesifikasi Anatomi Kode

### A. `app/Http/Requests/FinalizeWorkDayRequest.php`
```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeWorkDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_day_id' => ['required', 'integer', 'exists:work_days,id'],
            'digital_balances' => ['required', 'array'],
            'digital_balances.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'digital_balances.*.actual_balance' => ['required', 'integer', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

### B. Controller Action (`ReconciliationController.php`)
```php
namespace App\Http\Controllers\Api;

use App\Exceptions\UnresolvedDiscrepancyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinalizeWorkDayRequest;
use App\Models\WorkDay;
use App\Services\ClosingDayService;
use Illuminate\Http\JsonResponse;

class ReconciliationController extends Controller
{
    public function finalizeDay(
        FinalizeWorkDayRequest $request,
        ClosingDayService $closingService
    ): JsonResponse {
        $validated = $request->validated();
        $workDay = WorkDay::findOrFail($validated['work_day_id']);

        try {
            $updatedWorkDay = $closingService->finalizeWorkDay(
                workDay: $workDay,
                digitalBalances: $validated['digital_balances'],
                closingNotes: $validated['closing_notes'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Hari kerja berhasil difinalisasi. Selisih Rp0.',
                'data' => [
                    'work_day_id' => $updatedWorkDay->id,
                    'status' => $updatedWorkDay->status,
                    'closed_at' => $updatedWorkDay->closed_at->toISOString(),
                ],
            ], 200);
        } catch (UnresolvedDiscrepancyException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'discrepant_accounts' => $e->getDiscrepantAccounts(),
            ], 422);
        }
    }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Endpoint `POST /api/v1/reconciliation/finalize-day` merespons `200 OK` saat rekonsiliasi pas Rp0.
2. Endpoint merespons `422 Unprocessable Entity` saat terdapat selisih uang, menyertakan daftar akun yang selisih.
3. Status hari kerja di database tetap `needs_reconciliation` jika gagal validasi selisih.

---

## 5. Automated Tests Wajib
- **File:** `tests/Feature/Api/FinalizeWorkDayApiTest.php`
- **Metode Uji:**
  1. `test_successful_finalization_returns_200_ok()`:
     - Saldo fisik dan digital cocok $\rightarrow$ status 200, status hari berubah 'final'.
  2. `test_unbalanced_finalization_returns_422_with_discrepant_accounts()`:
     - Ada selisih Rp15.000 $\rightarrow$ status 422, response memuat daftar akun yang selisih.
